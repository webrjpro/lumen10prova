<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Lib.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
function lumen10prova_supports($feature) {
 switch($feature) {
  case FEATURE_MOD_INTRO: case FEATURE_BACKUP_MOODLE2: case FEATURE_COMPLETION_HAS_RULES: case FEATURE_GROUPS:
  case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
  case FEATURE_GRADE_HAS_GRADE: return false; // One native Quiz grade item; never duplicate course aggregation.
  case FEATURE_MOD_PURPOSE: return MOD_PURPOSE_ASSESSMENT;
  default:return null;
 }
}
/** Record standard viewing and the optional native view-completion rule. */
function lumen10prova_view($p, $course, $cm, $context): void {
 global $CFG;
 require_once($CFG->libdir.'/completionlib.php');
 $event=\mod_lumen10prova\event\course_module_viewed::create(['objectid'=>$p->id,'context'=>$context]);
 $event->add_record_snapshot('course',$course);
 $event->add_record_snapshot('lumen10prova',$p);
 $event->trigger();
 (new completion_info($course))->set_module_viewed($cm);
}
function lumen10prova_add_instance($data,$mform=null) {
 global $DB;$data=\mod_lumen10prova\engine::normalise($data);$data->timecreated=time();$data->timemodified=time();
 $data->quizid=0;$data->codehash='';$data->released=0;
 $id=$DB->insert_record('lumen10prova',$data);
 $data->id=$id;\mod_lumen10prova\engine::create_quiz($data);\mod_lumen10prova\appearance::save($data);
 return $id;
}
function lumen10prova_update_instance($data,$mform=null) {
 global $DB;$old=$DB->get_record('lumen10prova',['id'=>$data->instance],'*',MUST_EXIST);
 if(!empty($data->exitlock)!==!empty($old->exitlock)&&$DB->record_exists_select('quiz_attempts','quiz=? AND preview=0 AND state IN (?,?)',[$old->quizid,'inprogress','overdue']))throw new moodle_exception('securitypolicylocked','mod_lumen10prova');
 $data=\mod_lumen10prova\engine::normalise($data);$data->id=$old->id;$data->quizid=$old->quizid;$data->timemodified=time();
 if(\mod_lumen10prova\engine::has_attempts($old)&&\mod_lumen10prova\engine::structural_change($old,$data)) {
  throw new moodle_exception('lockedsettings','mod_lumen10prova');
 }
 $DB->update_record('lumen10prova',$data);\mod_lumen10prova\engine::update_quiz($data);\mod_lumen10prova\appearance::save($data);
 return true;
}
function lumen10prova_delete_instance($id) {
 global $DB,$CFG;$p=$DB->get_record('lumen10prova',['id'=>$id]);if(!$p)return false;
 if($p->quizid&&($cm=get_coursemodule_from_instance('quiz',$p->quizid,$p->course))) {
  require_once($CFG->dirroot.'/course/lib.php');course_delete_module($cm->id);
 }
 foreach(['log','live','gate'] as $t)$DB->delete_records('lumen10prova_'.$t,['provaid'=>$id]);
 $DB->delete_records('lumen10prova',['id'=>$id]);return true;
}
function lumen10prova_get_coursemodule_info($cm) {
 global $DB;$p=$DB->get_record('lumen10prova',['id'=>$cm->instance]);if(!$p)return null;
 $info=new cached_cm_info();$info->name=$p->name;$info->customdata=['customcompletionrules'=>[
  'completionattempt'=>$p->completionattempt,'completiongraded'=>$p->completiongraded,'completionpassed'=>$p->completionpassed]];return $info;
}
function lumen10prova_get_completion_state($course,$cm,$userid,$type) {
 global $DB;$p=$DB->get_record('lumen10prova',['id'=>$cm->instance],'*',MUST_EXIST);
 return \mod_lumen10prova\engine::completion($p,$userid,$type);
}
function lumen10prova_pluginfile($course,$cm,$context,$area,$args,$forcedownload,array $options=[]) {
 require_login($course,false,$cm);if($context->contextlevel!==CONTEXT_MODULE||!in_array($area,['intro','examlogo','examwatermark'],true))return false;
 $fs=get_file_storage();$itemid=array_shift($args);$name=array_pop($args);$path=$args?'/'.implode('/',$args).'/':'/';
 $file=$fs->get_file($context->id,'mod_lumen10prova',$area,$itemid,$path,$name);if(!$file||$file->is_directory())return false;
 send_stored_file($file,0,0,$forcedownload,$options);
}
/** Guard native Quiz web requests before any answers can be processed, including native AJAX. */
function lumen10prova_after_require_login($courseorid=null,$autologinguest=null,$cm=null,$setwantsurltome=null,$preventredirect=null) {
 global $DB,$USER,$CFG;
 if(CLI_SCRIPT||!$cm||empty($USER->id))return;
 $quizmodule=$DB->get_field('modules','id',['name'=>'quiz']);if($cm->module!=$quizmodule)return;
 $p=\mod_lumen10prova\engine::lookup((int)$cm->instance);if(!$p)return;
 $context=\mod_lumen10prova\engine::context($p);
 if(has_capability('mod/lumen10prova:manage',$context,$USER)||has_capability('mod/lumen10prova:grade',$context,$USER)||has_capability('mod/lumen10prova:monitor',$context,$USER))return;
 $reason=\mod_lumen10prova\engine::access_reason($p,(int)$USER->id);
 if($reason)throw new moodle_exception($reason,'mod_lumen10prova',new moodle_url('/mod/lumen10prova/view.php',['id'=>$context->instanceid]));
 $attempts=$DB->get_records_select('quiz_attempts','quiz=? AND userid=? AND preview=0 AND state IN (?,?)',[$p->quizid,$USER->id,'inprogress','overdue']);
 foreach($attempts as $a){require_once($CFG->dirroot.'/mod/quiz/locallib.php');$obj=\mod_quiz\quiz_attempt::create($a->id);$end=\mod_lumen10prova\engine::deadline($p,$a,$obj->get_quiz());
  if($end&&time()>=$end){
   // AJAX opens a native transaction before login. Rejecting its late response
   // rolls back that transaction; leave finalisation to the scheduled task.
   if(!$DB->is_transaction_started()){$obj->handle_if_time_expired(time(),false);\mod_lumen10prova\engine::audit($p,$USER->id,$a->id,'late_request_blocked');}
   throw new moodle_exception('attemptalreadyclosed','quiz',new moodle_url('/mod/quiz/review.php',['attempt'=>$a->id]));
  }
  $path=parse_url($_SERVER['SCRIPT_NAME']??'',PHP_URL_PATH);
  if((defined('WS_SERVER')&&WS_SERVER)||in_array($path,['/mod/quiz/processattempt.php','/mod/quiz/autosave.ajax.php'],true))\mod_lumen10prova\attempt_security::guard_write($p,$a);
 }
 // Native attempt objects may have been loaded before require_login. Abort a stale
 // request if review policy changed, so fresh objects use the new fail-closed masks.
 if(\mod_lumen10prova\engine::review_policy($p))throw new moodle_exception('notavailable','mod_lumen10prova',new moodle_url('/mod/lumen10prova/view.php',['id'=>$context->instanceid]));
}
