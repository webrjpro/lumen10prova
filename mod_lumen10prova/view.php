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
 * View.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->libdir.'/completionlib.php');
$id = required_param('id', PARAM_INT);
[$p,$cm,$course,$context] = \mod_lumen10prova\ui::load($id);
require_once($CFG->dirroot.'/mod/lumen10prova/lib.php');
lumen10prova_view($p,$course,$cm,$context);
$quizcm = get_coursemodule_from_instance('quiz',$p->quizid,$course->id,false,MUST_EXIST);
$actions=[];
if (has_capability('mod/lumen10prova:manage',$context)) {
 foreach ([['/mod/quiz/edit.php',['cmid'=>$quizcm->id],'questionbank'],
  ['/mod/lumen10prova/import.php',['id'=>$cm->id],'importbutton'],
  ['/mod/quiz/overrides.php',['cmid'=>$quizcm->id],'individualtime']] as [$path,$params,$key]) {
  $actions[]=\mod_lumen10prova\ui::action($path,$params,get_string($key,'mod_lumen10prova'));
 }
 $actions[]=\mod_lumen10prova\ui::action('/course/modedit.php',['update'=>$cm->id],get_string('settings'));
}
foreach (['room'=>'monitor','grading'=>'grade','reports'=>'monitor'] as $page=>$capability) {
 if(has_capability('mod/lumen10prova:'.$capability,$context)) {
  $actions[]=\mod_lumen10prova\ui::action('/mod/lumen10prova/'.$page.'.php',['id'=>$cm->id],get_string($page,'mod_lumen10prova'));
 }
}
if($p->aienabled && has_capability('mod/lumen10prova:ai',$context)) {
 $actions[]=\mod_lumen10prova\ui::action('/mod/lumen10prova/ai.php',['id'=>$cm->id],get_string('aititle','mod_lumen10prova'));
}
$why=\mod_lumen10prova\engine::access_reason($p,(int)$USER->id);
$available=!$why || has_capability('mod/lumen10prova:manage',$context);
$data=['intro'=>format_module_intro('lumen10prova',$p,$cm->id),'exitlock'=>!empty($p->exitlock),
 'time'=>format_time($p->timelimit),'attempts'=>$p->attempts ?: get_string('unlimited','mod_lumen10prova'),
 'grade'=>format_float($p->grade,2),'gradepass'=>format_float($p->gradepass,2),'actions'=>$actions,
 'available'=>$available,'modal'=>$available&&!empty($p->modalenabled),
 'quizurl'=>(new moodle_url('/mod/quiz/view.php',['id'=>$quizcm->id]))->out(false),
 'startlabel'=>get_string(!empty($p->modalenabled)?'openfullpage':'start','mod_lumen10prova'),
 'unavailable'=>$why?get_string($why,'mod_lumen10prova'):''];
\mod_lumen10prova\ui::header($p,$cm,$course,'view');
if($data['modal'])$PAGE->requires->js_call_amd('mod_lumen10prova/exam_modal','init',[$data['quizurl'],(int)context_module::instance($quizcm->id)->id,\mod_lumen10prova\appearance::settings($p)]);
\mod_lumen10prova\ui::render('view',$data); \mod_lumen10prova\ui::footer();
