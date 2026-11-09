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
 * Grading.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');require_once($CFG->dirroot.'/mod/quiz/locallib.php');
$id=required_param('id',PARAM_INT);[$p,$cm,$course,$context]=\mod_lumen10prova\ui::load($id);
require_capability('mod/lumen10prova:grade',$context);
$quizcm=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);
require_capability('mod/quiz:grade',context_module::instance($quizcm->id));
$aid=optional_param('attempt',0,PARAM_INT);$slot=optional_param('slot',0,PARAM_INT);$obj=null;
if($aid) {
 $a=$DB->get_record('quiz_attempts',['id'=>$aid,'quiz'=>$p->quizid,'preview'=>0,'state'=>'finished'],'*',MUST_EXIST);
 $obj=\mod_quiz\quiz_attempt::create($aid);
 if($slot&&!in_array($slot,array_map('intval',$obj->get_slots()),true))throw new moodle_exception('invalidrequest');
 if($_SERVER['REQUEST_METHOD']==='POST') {
  require_sesskey();if(!$slot||!question_engine::is_manual_grade_in_range($obj->get_uniqueid(),$slot))throw new moodle_exception('invalidsettings','mod_lumen10prova');
  $tx=$DB->start_delegated_transaction();$obj->process_submitted_actions(time());$tx->allow_commit();
  \mod_lumen10prova\engine::audit($p,$a->userid,$aid,'manual_grade_saved','slot='.$slot);
  \mod_lumen10prova\engine::review_policy($p);\mod_lumen10prova\engine::complete_user($p,$a->userid);
  redirect(new moodle_url('/mod/lumen10prova/grading.php',['id'=>$id,'attempt'=>$aid]),get_string('changessaved'));
 }
}
$links=[];$title='';$question='';
if($obj) {
 $live=\mod_lumen10prova\engine::live($p,$a);
 $title=$p->anonymousgrading?$live->candidate:fullname($DB->get_record('user',['id'=>$a->userid],'*',MUST_EXIST));
 if(!$slot) {
  foreach($obj->get_slots()as$s) {$qa=$obj->get_question_attempt($s);
   if($qa->get_question()->qtype->is_manual_graded())$links[]=\mod_lumen10prova\ui::action('/mod/lumen10prova/grading.php',['id'=>$id,'attempt'=>$aid,'slot'=>$s],get_string('questionstate','mod_lumen10prova',(object)['slot'=>$s,'state'=>$qa->get_state()->default_string(true)]));
  }
 }
} else {
 $attempts=$DB->get_records('quiz_attempts',['quiz'=>$p->quizid,'preview'=>0,'state'=>'finished'],'id ASC','*',0,500);
 $uids=array_values(array_unique(array_column($attempts,'userid')));
 $users=$uids?$DB->get_records_list('user','id',$uids):[];
 foreach($attempts as$a) {
  $live=\mod_lumen10prova\engine::live($p,$a);$label=$p->anonymousgrading?$live->candidate:fullname($users[$a->userid]);
  $links[]=\mod_lumen10prova\ui::action('/mod/lumen10prova/grading.php',['id'=>$id,'attempt'=>$a->id],get_string('attemptlabel','mod_lumen10prova',(object)['name'=>$label,'attempt'=>$a->attempt]));
 }
 if(!$p->anonymousgrading)$links[]=\mod_lumen10prova\ui::action('/mod/quiz/report.php',['id'=>$quizcm->id,'mode'=>'grading'],get_string('nativegrading','mod_lumen10prova'));
}
\mod_lumen10prova\ui::header($p,$cm,$course,'grading');
// Native question rendering requires the page header and initialises its own AMD modules.
if($obj&&$slot)$question=$obj->render_question_for_commenting($slot);
\mod_lumen10prova\ui::render('grading',['id'=>$id,'sesskey'=>sesskey(),'attempt'=>$aid,'slot'=>$slot,'title'=>$title,
 'rubric'=>$p->rubric,'links'=>$links,'question'=>$question]);\mod_lumen10prova\ui::footer();
