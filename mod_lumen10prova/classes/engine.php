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
 * Engine.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
/** Native Quiz ownership and institutional policy. No question or grade engine is duplicated. */
final class engine {
 public static function normalise(\stdClass $data): \stdClass {
  $defaults=['modalenabled'=>1,'paperstyle'=>1,'answersheetenabled'=>1,'watermarkopacity'=>0.06,'intro'=>'','introformat'=>FORMAT_HTML,'timeopen'=>0,'timeclose'=>0,'timelimit'=>3600,'attempts'=>1,'grade'=>10,'gradepass'=>6,'grademethod'=>1,'navigation'=>'free','blocksize'=>10,'shufflequestions'=>1,'shuffleanswers'=>1,'releasepolicy'=>'close','releasetime'=>0,'showmarks'=>1,'showfeedback'=>1,'showanswers'=>0,'codeenabled'=>0,'manualstart'=>0,'syncstart'=>0,'groupid'=>0,'profilefields'=>'','regulations'=>'','alerts'=>'900,300,60','rubric'=>'','monitorenabled'=>1,'logevents'=>0,'fullscreen'=>0,'blockcopy'=>0,'anonymousgrading'=>0,'aienabled'=>0,'completionattempt'=>0,'completiongraded'=>0,'completionpassed'=>0];
  foreach($defaults as $k=>$v)if(!isset($data->$k))$data->$k=$v;
  $errors=self::validation((array)$data,(int)$data->course);if($errors)throw new \moodle_exception('invalidsettings','mod_lumen10prova');
  $data->exitlock=!empty($data->exitlock)?1:0;if($data->exitlock)$data->monitorenabled=1;
  $data->syncstart=$data->manualstart&&!empty($data->syncstart)?1:0;$data->logevents=$data->monitorenabled&&!empty($data->logevents)?1:0;
  return $data;
 }
 public static function validation(array $d,int $courseid): array {
  global $DB;$e=[];$message=get_string('invalidsettings','mod_lumen10prova');
  foreach(['timelimit'=>[60,604800],'attempts'=>[0,20],'grade'=>[0.001,1000],'gradepass'=>[0,(float)($d['grade']??10)],'blocksize'=>[1,100]] as $k=>$r){if(isset($d[$k])&&(!is_numeric($d[$k])||!is_finite((float)$d[$k])||$d[$k]<$r[0]||$d[$k]>$r[1]))$e[$k]=$message;}
  if(isset($d['watermarkopacity'])&&(!is_numeric($d['watermarkopacity'])||!is_finite((float)$d['watermarkopacity'])||$d['watermarkopacity']<0.02||$d['watermarkopacity']>0.15))$e['watermarkopacity']=$message;
  if(!empty($d['timeclose'])&&!empty($d['timeopen'])&&$d['timeclose']<=$d['timeopen'])$e['timeclose']=$message;
  if(!in_array($d['navigation']??'free',['free','seq','blocks'],true))$e['navigation']=$message;
  if(!in_array($d['releasepolicy']??'close',['immediate','close','graded','date','manual'],true))$e['releasepolicy']=$message;
  if(($d['releasepolicy']??'')==='date'&&empty($d['releasetime']))$e['releasetime']=$message;
  if(!empty($d['groupid'])&&!$DB->record_exists('groups',['id'=>$d['groupid'],'courseid'=>$courseid]))$e['groupid']=$message;
  if(!preg_match('/^\s*\d+(\s*,\s*\d+){0,7}\s*$/',$d['alerts']??'900,300,60'))$e['alerts']=$message;
  foreach(explode(',',$d['profilefields']??'') as $f){$f=trim($f);if($f!==''&&!in_array($f,['firstname','lastname','email','idnumber','phone1','city','country'],true)&&!preg_match('/^profile_[a-zA-Z0-9_]{1,80}$/',$f))$e['profilefields']=$message;}
  if(!empty($d['subnet'])){foreach(preg_split('/[\s,;]+/',$d['subnet'],-1,PREG_SPLIT_NO_EMPTY) as $ip){if(!\core\ip_utils::is_ip_address($ip)&&!\core\ip_utils::is_ipv4_range($ip)&&!\core\ip_utils::is_ipv6_range($ip))$e['subnet']=$message;}}
  return $e;
 }
 public static function structural_change(\stdClass $old,\stdClass $new): bool {
  foreach(['timelimit','timeopen','timeclose','attempts','grade','gradepass','grademethod','navigation','blocksize','shufflequestions','shuffleanswers','manualstart','syncstart'] as $k)if(isset($new->$k)&&(string)$old->$k!==(string)$new->$k)return true;
  return false;
 }
 public static function has_attempts(\stdClass $p): bool {
  global $DB;return !empty($p->quizid)&&$DB->record_exists('quiz_attempts',['quiz'=>$p->quizid,'preview'=>0]);
 }
 public static function quiz_data(\stdClass $p): \stdClass {
  $q=(object)['course'=>$p->course,'name'=>$p->name,'intro'=>$p->intro,'introformat'=>$p->introformat,'timeopen'=>$p->timeopen,'timeclose'=>$p->timeclose,'timelimit'=>$p->timelimit,'attempts'=>$p->attempts,'attemptonlast'=>0,'grademethod'=>$p->grademethod,'decimalpoints'=>2,'questiondecimalpoints'=>-1,'questionsperpage'=>$p->navigation==='blocks'?$p->blocksize:1,'navmethod'=>$p->navigation==='free'?'free':'seq','shuffleanswers'=>$p->shuffleanswers,'sumgrades'=>0,'grade'=>$p->grade,'gradepass'=>$p->gradepass,'overduehandling'=>'autosubmit','graceperiod'=>0,'quizpassword'=>$p->exampassword??'','subnet'=>$p->subnet??'','browsersecurity'=>'','delay1'=>0,'delay2'=>0,'showuserpicture'=>0,'showblocks'=>0,'completion'=>0,'completionview'=>0,'completionusegrade'=>0,'completionpassgrade'=>0,'completionminattempts'=>0,'completionattemptsexhausted'=>0,'allowofflineattempts'=>0,'preferredbehaviour'=>'deferredfeedback'];
  foreach(['attempt','correctness','maxmarks','marks','specificfeedback','generalfeedback','rightanswer','overallfeedback'] as $n)foreach(['during','immediately','open','closed'] as $period)$q->{$n.$period}=($n==='attempt'&&$period==='during')||($n==='maxmarks'&&$period==='during')?1:0;
  return $q;
 }
 public static function create_quiz(\stdClass $p): int {
  global $CFG,$DB;require_once($CFG->dirroot.'/course/modlib.php');
  if(!class_exists('quizaccess_lumen10prova',false))require_once($CFG->dirroot.'/mod/quiz/accessrule/lumen10prova/rule.php');
  $owncm=$DB->get_record('course_modules',['id'=>$p->coursemodule??0]);
  $q=self::quiz_data($p);$q->module=$DB->get_field('modules','id',['name'=>'quiz'],MUST_EXIST);$q->modulename='quiz';
  $q->section=0;$q->visible=1;$q->visibleoncoursepage=0;$q->availability=null;$q->groupmode=0;$q->groupingid=0;$q->showdescription=0;
  $q->cmidnumber='';
  if($owncm){$q->section=(int)$DB->get_field('course_sections','section',['id'=>$owncm->section]);}
  $course=$DB->get_record('course',['id'=>$p->course],'*',MUST_EXIST);$created=add_moduleinfo($q,$course);
  $DB->set_field('lumen10prova','quizid',$created->instance,['id'=>$p->id]);$p->quizid=$created->instance;
  $DB->set_field('quiz_sections','shufflequestions',(int)$p->shufflequestions,['quizid'=>$p->quizid]);
  self::review_policy($p);return (int)$p->quizid;
 }
 public static function update_quiz(\stdClass $p): void {
  global $CFG,$DB;require_once($CFG->dirroot.'/mod/quiz/lib.php');
  $old=$DB->get_record('quiz',['id'=>$p->quizid],'*',MUST_EXIST);$cm=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);
  $q=self::quiz_data($p);$q->instance=$p->quizid;$q->coursemodule=$cm->id;
  if(!isset($p->exampassword))$q->quizpassword=$old->password;if(!isset($p->subnet))$q->subnet=$old->subnet;
  $result=quiz_update_instance($q,null);if($result!==true)throw new \moodle_exception('invalidsettings','mod_lumen10prova');
  if((float)$old->grade!==(float)$p->grade)\mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->update_quiz_maximum_grade((float)$p->grade);
  $item=\grade_item::fetch(['itemtype'=>'mod','itemmodule'=>'quiz','iteminstance'=>$p->quizid,'itemnumber'=>0,'courseid'=>$p->course]);
  if($item){$item->gradepass=(float)$p->gradepass;$item->update();}
  if(!self::has_attempts($p)){
   $DB->set_field('quiz_sections','shufflequestions',(int)$p->shufflequestions,['quizid'=>$p->quizid]);
   quiz_repaginate_questions($p->quizid,$q->questionsperpage);
  }
  self::review_policy($p);rebuild_course_cache($p->course,true);
 }
 public static function context(\stdClass $p): \context_module {
  $cm=get_coursemodule_from_instance('lumen10prova',$p->id,$p->course,false,MUST_EXIST);return \context_module::instance($cm->id);
 }
 public static function lookup(int $quizid): ?\stdClass {
  global $DB;return $DB->get_record('lumen10prova',['quizid'=>$quizid])?:null;
 }
 public static function sync_visibility(\stdClass $p): void {
  global $DB,$CFG;require_once($CFG->dirroot.'/course/lib.php');
  $cm=get_coursemodule_from_instance('lumen10prova',$p->id,$p->course);
  $backend=get_coursemodule_from_instance('quiz',$p->quizid,$p->course);
  if(!$cm||!$backend)return;
  $changed=false;
  if($backend->name!==$p->name){
   (new \core_courseformat\local\cmactions(get_course($p->course)))->rename((int)$backend->id,$p->name);$changed=true;
   $backend=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);
  }
  if($backend->section!=$cm->section){
   $section=$DB->get_record('course_sections',['id'=>$cm->section,'course'=>$p->course],'*',MUST_EXIST);
   if(method_exists(\core_courseformat\local\cmactions::class,'move_end_section')) {
    (new \core_courseformat\local\cmactions(get_course($p->course)))->move_end_section((int)$backend->id,(int)$section->id);
   } else {
    moveto_module($backend,$section);
   }
   $changed=true;
   $backend=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);
  }
  if($backend->visible!=$cm->visible||$backend->visibleoncoursepage!=0){
   set_coursemodule_visible($backend->id,(int)$cm->visible,0,false);$changed=true;
   // The native API also changes visibleold; compare the updated values below.
   $backend=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);
  }
  $patch=(object)['id'=>$backend->id];
  foreach(['visibleold','availability','groupmode','groupingid'] as $field){if($backend->$field!=$cm->$field){$patch->$field=$cm->$field;$changed=true;}}
  if(count((array)$patch)>1)$DB->update_record('course_modules',$patch);
  if($changed)rebuild_course_cache($p->course,true);
 }
 public static function participant(\stdClass $p,int $uid): bool {
  global $DB;$u=$DB->get_record('user',['id'=>$uid],'id,deleted,suspended');if(!$u||$u->deleted||$u->suspended||isguestuser($u))return false;
  $ctx=self::context($p);$cm=get_fast_modinfo($p->course,$uid)->get_cm($ctx->instanceid);
  return $cm->uservisible&&is_enrolled(\context_course::instance($p->course),$uid,'',true)&&has_capability('mod/lumen10prova:view',$ctx,$uid)&&(!$p->groupid||groups_is_member($p->groupid,$uid));
 }
 public static function access_reason(\stdClass $p,int $uid): string {
  if(!self::participant($p,$uid))return 'notavailable';
  global $DB;$u=$DB->get_record('user',['id'=>$uid],'*',MUST_EXIST);
  foreach(explode(',',$p->profilefields) as $field){$field=trim($field);if($field==='')continue;
   if(str_starts_with($field,'profile_')){$fid=$DB->get_field('user_info_field','id',['shortname'=>substr($field,8)]);$value=$fid?$DB->get_field('user_info_data','data',['userid'=>$uid,'fieldid'=>$fid]):false;}else{$value=$u->$field??'';}
   if(trim((string)$value)==='')return 'needprofile';
  }
  $gate=self::gate($p,$uid,false);if($p->manualstart&&(!$gate||!$gate->releaseat||$gate->releaseat>time()))return 'waiting';
  return attempt_security::reason($p,$uid);
 }
 public static function gate(\stdClass $p,int $uid,bool $create=true): ?\stdClass {
  global $DB;$g=$DB->get_record('lumen10prova_gate',['provaid'=>$p->id,'userid'=>$uid]);if($g||!$create)return $g?:null;
  $lock=\core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('gate-'.$p->id.'-'.$uid,10);if(!$lock)throw new \moodle_exception('notavailable','mod_lumen10prova');
  try{$g=$DB->get_record('lumen10prova_gate',['provaid'=>$p->id,'userid'=>$uid]);if(!$g){$id=$DB->insert_record('lumen10prova_gate',(object)['provaid'=>$p->id,'userid'=>$uid]);$g=$DB->get_record('lumen10prova_gate',['id'=>$id],'*',MUST_EXIST);}return $g;}finally{$lock->release();}
 }
 public static function accepted(\stdClass $p,int $uid): bool {
  $text=self::policy_text($p);$g=self::gate($p,$uid,false);return !$text||($g&&hash_equals(hash('sha256',$text),$g->acceptedhash));
 }
 public static function record_acceptance(\stdClass $p,int $uid): void {
  global $DB;$g=self::gate($p,$uid);$g->acceptedhash=hash('sha256',self::policy_text($p));$g->acceptedat=time();$DB->update_record('lumen10prova_gate',$g);self::audit($p,$uid,0,'regulations_accepted',substr($g->acceptedhash,0,16));
 }
 public static function policy_text(\stdClass $p):string{return $p->regulations.(!empty($p->exitlock)?"\n".get_string('securitynotice','mod_lumen10prova'):'');}
 public static function release(\stdClass $p,array $userids,int $now=0): int {
  global $DB;$now=$now?:time();$count=0;
  $lock=\core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('release-'.$p->id,10);if(!$lock)throw new \moodle_exception('notavailable','mod_lumen10prova');
  try{foreach(array_unique(array_map('intval',$userids)) as $uid){if(!self::participant($p,$uid))continue;$g=self::gate($p,$uid);if($g->releaseat)continue;$g->releaseat=$now;$DB->update_record('lumen10prova_gate',$g);self::audit($p,$uid,0,'released');$count++;}}finally{$lock->release();}
  return $count;
 }
 public static function deadline(\stdClass $p,\stdClass $attempt,\stdClass $quiz): int {
  $times=[];if($quiz->timelimit)$times[]=$attempt->timestart+$quiz->timelimit;if($quiz->timeclose)$times[]=$quiz->timeclose;
  if($p->manualstart&&$p->syncstart&&($g=self::gate($p,$attempt->userid,false))&&$g->releaseat&&$quiz->timelimit)$times[]=max((int)$g->releaseat,(int)($quiz->timeopen??0))+$quiz->timelimit;
  return $times?min($times):0;
 }
 public static function closed(\stdClass $p): bool {
  global $DB;$close=(int)$p->timeclose;if(!$close)return false;
  $overrides=$DB->get_records('quiz_overrides',['quiz'=>$p->quizid]);foreach($overrides as $o){if($o->timeclose!==null){if(!(int)$o->timeclose)return false;$close=max($close,(int)$o->timeclose);}}
  if(time()<$close)return false;
  return !$DB->record_exists_select('quiz_attempts','quiz=? AND preview=0 AND state IN (?,?)',[$p->quizid,'inprogress','overdue']);
 }
 public static function grade_ready(\stdClass $p,int $uid): bool {
  global $DB;return !$DB->record_exists_select('quiz_attempts','quiz=? AND userid=? AND preview=0 AND state=? AND sumgrades IS NULL',[$p->quizid,$uid,'finished']);
 }
 public static function results_open(\stdClass $p): bool {
  global $DB;switch($p->releasepolicy){case 'immediate':return true;case 'close':return self::closed($p);case 'date':return $p->releasetime&&time()>=$p->releasetime;case 'manual':return (bool)$p->released;case 'graded':return self::closed($p)&&!$DB->record_exists_select('quiz_attempts','quiz=? AND preview=0 AND state=? AND sumgrades IS NULL',[$p->quizid,'finished']);}return false;
 }
 public static function review_policy(\stdClass $p): bool {
  global $CFG,$DB;require_once($CFG->dirroot.'/mod/quiz/lib.php');$q=$DB->get_record('quiz',['id'=>$p->quizid]);if(!$q)return false;
  $D=\mod_quiz\question\display_options::DURING;$bits=\mod_quiz\question\display_options::IMMEDIATELY_AFTER|\mod_quiz\question\display_options::LATER_WHILE_OPEN|\mod_quiz\question\display_options::AFTER_CLOSE;
  $open=self::results_open($p);$safe=$open&&self::closed($p);$values=['reviewattempt'=>$D|($open?$bits:0),'reviewmaxmarks'=>$D|($open?$bits:0),'reviewmarks'=>$open&&$p->showmarks?$bits:0,'reviewcorrectness'=>$safe&&$p->showanswers?$bits:0,'reviewrightanswer'=>$safe&&$p->showanswers?$bits:0,'reviewspecificfeedback'=>$safe&&$p->showfeedback?$bits:0,'reviewgeneralfeedback'=>$safe&&$p->showfeedback?$bits:0,'reviewoverallfeedback'=>$safe&&$p->showfeedback?$bits:0];
  $changed=false;foreach($values as $k=>$v){if((int)$q->$k!==$v){$DB->set_field('quiz',$k,$v,['id'=>$q->id]);$q->$k=$v;$changed=true;}}
  if($changed){quiz_grade_item_update($q);rebuild_course_cache($p->course,true);}
  return $changed;
 }
 public static function completion(\stdClass $p,int $uid,int $type=COMPLETION_AND): bool {
  global $DB;$states=[];$delivered=$DB->record_exists('quiz_attempts',['quiz'=>$p->quizid,'userid'=>$uid,'preview'=>0,'state'=>'finished']);
  $grade=$DB->get_field_sql('SELECT g.finalgrade FROM {grade_items} i JOIN {grade_grades} g ON g.itemid=i.id WHERE i.itemtype=? AND i.itemmodule=? AND i.iteminstance=? AND i.itemnumber=0 AND g.userid=?',['mod','quiz',$p->quizid,$uid]);
  $graded=$delivered&&$grade!==false&&$grade!==null&&self::grade_ready($p,$uid)&&self::results_open($p);
  if($p->completionattempt)$states[]=$delivered;if($p->completiongraded)$states[]=$graded;if($p->completionpassed)$states[]=$graded&&(float)$grade>=(float)$p->gradepass;
  if(!$states)return $type===COMPLETION_AND;return $type===COMPLETION_AND?!in_array(false,$states,true):in_array(true,$states,true);
 }
 public static function complete_user(\stdClass $p,int $uid): void {
  global $CFG;require_once($CFG->libdir.'/completionlib.php');
  $course=get_course($p->course);$cm=get_coursemodule_from_instance('lumen10prova',$p->id,$p->course);if(!$cm)return;
  $completion=new \completion_info($course);if($completion->is_enabled($cm))$completion->update_state($cm,COMPLETION_UNKNOWN,$uid);
 }
 public static function audit(\stdClass $p,int $uid,int $attempt,string $kind,string $detail=''): void {
  global $DB,$USER;$id=$DB->insert_record('lumen10prova_log',(object)['provaid'=>$p->id,'userid'=>$uid,'attemptid'=>$attempt,'actorid'=>$USER->id??0,'kind'=>$kind,'detail'=>\core_text::substr($detail,0,2000),'timecreated'=>time()]);
  if(!in_array($kind,['focus_lost','focus_return','fullscreen_left','ip_changed','device_changed','reconnected','clipboard_blocked'],true)) {
   \mod_lumen10prova\event\action_recorded::create(['context'=>self::context($p),'objectid'=>$id,'relateduserid'=>$uid?:null,'other'=>['kind'=>$kind,'attemptid'=>$attempt]])->trigger();
  }
 }
 public static function live(\stdClass $p,\stdClass $a): \stdClass {
  global $DB;$row=$DB->get_record('lumen10prova_live',['attemptid'=>$a->id]);if($row)return $row;
  $id=$DB->insert_record('lumen10prova_live',(object)['provaid'=>$p->id,'attemptid'=>$a->id,'userid'=>$a->userid,'candidate'=>'C-'.strtoupper(bin2hex(random_bytes(8))),'lastseen'=>time()]);return $DB->get_record('lumen10prova_live',['id'=>$id],'*',MUST_EXIST);
 }
 public static function process(\stdClass $p): void {
  global $DB,$CFG;require_once($CFG->dirroot.'/mod/quiz/locallib.php');$rows=$DB->get_records_select('quiz_attempts','quiz=? AND preview=0 AND state IN (?,?)',[$p->quizid,'inprogress','overdue']);
  foreach($rows as $a){$obj=\mod_quiz\quiz_attempt::create($a->id);$end=self::deadline($p,$a,$obj->get_quiz());if($end&&time()>=$end){$obj->handle_if_time_expired(time(),false);self::audit($p,$a->userid,$a->id,'deadline_processed');}
   else if(!empty($p->exitlock)&&attempt_security::blocked($p,$a)){$lock=\core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('live-'.$a->id,5);if($lock){try{$row=self::live($p,$a);attempt_security::heartbeat($p,$a,$row,'',(int)$row->securitygeneration,time());}finally{$lock->release();}}}
  }
  self::review_policy($p);
  $users=$DB->get_fieldset_select('quiz_attempts','DISTINCT userid','quiz=? AND preview=0',[$p->quizid]);foreach($users as $uid)self::complete_user($p,(int)$uid);
 }
 /** Bulk monitor data; access restrictions are still evaluated for each participant. */
 public static function status_rows(\stdClass $p): array {
  global $DB;
  $context = self::context($p);
  $fields = 'u.id,u.deleted,u.suspended,u.'.implode(',u.',\core_user\fields::get_name_fields());
  $users = get_enrolled_users(\context_course::instance($p->course), 'mod/quiz:attempt', $p->groupid,
      $fields, 'u.lastname,u.firstname', 0, 0, true);
  if (!$users) { return []; }
  [$insql, $params] = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'monitoruser');
  $params['quiz'] = $p->quizid;
  $attempts = $DB->get_records_sql("SELECT a.userid, a.* FROM {quiz_attempts} a
      WHERE a.quiz=:quiz AND a.preview=0 AND a.userid $insql
      AND NOT EXISTS (SELECT 1 FROM {quiz_attempts} newer WHERE newer.quiz=a.quiz
          AND newer.userid=a.userid AND newer.preview=0 AND newer.attempt>a.attempt)", $params);
  $live = $DB->get_records_sql('SELECT l.userid,l.* FROM {lumen10prova_live} l
      JOIN {quiz_attempts} a ON a.id=l.attemptid
      WHERE l.provaid=? AND a.preview=0 AND NOT EXISTS (SELECT 1 FROM {quiz_attempts} newer
          WHERE newer.quiz=a.quiz AND newer.userid=a.userid AND newer.preview=0 AND newer.attempt>a.attempt)', [$p->id]);
  $gates = $p->manualstart && $p->syncstart ?
      $DB->get_records('lumen10prova_gate', ['provaid' => $p->id], '', 'userid,releaseat') : [];
  $quiz = \mod_quiz\quiz_settings::create($p->quizid)->get_quiz();
  $overrides = $DB->get_records('quiz_overrides', ['quiz' => $p->quizid]);
  $members = [];
  if ($overrides) {
   $groupids = array_filter(array_column($overrides, 'groupid'));
   if ($groupids) {
    [$groupsql, $groupparams] = $DB->get_in_or_equal(array_values($groupids));
    foreach ($DB->get_records_sql('SELECT id,userid,groupid FROM {groups_members} WHERE groupid '.$groupsql, $groupparams) as $member) {
     $members[$member->userid][$member->groupid] = true;
    }
   }
  }
  $rows = []; $now = time();
  foreach ($users as $u) {
   if ($u->deleted || $u->suspended || isguestuser($u) ||
       !has_capability('mod/lumen10prova:view', $context, $u->id) ||
       !get_fast_modinfo($p->course, $u->id)->get_cm($context->instanceid)->uservisible) { continue; }
   $a = $attempts[$u->id] ?? null;
   $r = $live[$u->id] ?? null;
   if ($r && (!$a || $r->attemptid != $a->id)) { $r = null; }
   $status = $a ? $a->state : 'notstarted';
   if ($p->monitorenabled && $a && $a->state === 'inprogress' && (!$r || $now - $r->lastseen > 90)) {
    $status = 'unresponsive';
   }
   if ($a && attempt_security::enabled($p, $a) && $r &&
       ($r->blockedat || $now - $r->lastseen > attempt_security::SILENCE_LIMIT)) { $status = 'blocked'; }
   $remaining = 0;
   if ($a && in_array($a->state, ['inprogress', 'overdue'], true)) {
    // Same precedence as native quiz_update_effective_access: user, then most generous group, then default.
    $q = clone $quiz;
    foreach (['timeopen', 'timeclose', 'timelimit'] as $field) {
     $own = null; $values = [];
     foreach ($overrides as $override) {
      if ((int)$override->userid === (int)$u->id && $override->$field !== null) { $own = $override->$field; }
      if ($override->groupid && isset($members[$u->id][$override->groupid]) && $override->$field !== null) {
       $values[] = (int)$override->$field;
      }
     }
     if ($own !== null) { $q->$field = (int)$own; }
     else if ($values) { $q->$field = $field === 'timeopen' ? min($values) : (in_array(0, $values, true) ? 0 : max($values)); }
    }
    $ends = [];
    if ($q->timelimit) { $ends[] = $a->timestart + $q->timelimit; }
    if ($q->timeclose) { $ends[] = $q->timeclose; }
    if ($p->manualstart && $p->syncstart && !empty($gates[$u->id]->releaseat) && $q->timelimit) {
     $ends[] = max((int)$gates[$u->id]->releaseat, (int)$q->timeopen) + $q->timelimit;
    }
    $remaining = $ends ? max(0, min($ends) - $now) : 0;
   }
   $rows[] = ['userid'=>(int)$u->id,'name'=>fullname($u),'attempt'=>$a?(int)$a->id:0,
       'status'=>$status,'remaining'=>$remaining,'lastseen'=>$r?(int)$r->lastseen:0,
       'candidate'=>$r?$r->candidate:'','focuschanges'=>$r?(int)$r->focuschanges:0,
       'reconnections'=>$r?(int)$r->reconnections:0,'ipchanges'=>$r?(int)$r->ipchanges:0,
       'devicechanges'=>$r?(int)$r->devicechanges:0];
  }
  return $rows;
 }
}
