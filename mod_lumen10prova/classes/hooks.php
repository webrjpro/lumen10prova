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
 * Hooks.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

/** Keep the owned Quiz accessible through Prova, without a second course card. */
final class hooks {
 public static function before_head(\core\hook\output\before_standard_head_html_generation $hook): void {
  global $DB,$PAGE;
  if(!get_config('mod_lumen10prova','version')||empty($PAGE->course->id))return;
  $ids=$DB->get_fieldset_sql("SELECT cm.id FROM {lumen10prova} p JOIN {course_modules} cm ON cm.instance=p.quizid AND cm.course=p.course JOIN {modules} m ON m.id=cm.module AND m.name='quiz' WHERE p.course=?",[$PAGE->course->id]);
  if($ids){
   $selectors=[];foreach($ids as$id){$id=(int)$id;$selectors[]='#module-'.$id;$selectors[]='#course-index-cm-'.$id;$selectors[]='[data-for="cm"][data-id="'.$id.'"]';}
   // Presentation only. Access still follows native visibility and Prova permissions.
   $hook->add_html('<style id="l10-owned-quiz-cards">'.implode(',',$selectors).'{display:none!important}</style>');
  }
  $cm=$PAGE->cm;
  $examroutes=['/mod/quiz/view.php','/mod/quiz/attempt.php','/mod/quiz/summary.php','/mod/quiz/review.php'];
  if(in_array($PAGE->url->get_path(),$examroutes,true)&&$cm&&$cm->modname==='quiz'&&($p=engine::lookup((int)$cm->instance))&&($p->modalenabled||$p->paperstyle||$p->answersheetenabled||!empty($p->exitlock))){
   $options=appearance::settings($p);$aid=(int)$PAGE->url->get_param('attempt');
   $attempt=$aid?$DB->get_record('quiz_attempts',['id'=>$aid,'quiz'=>$p->quizid],'id,preview'):null;
   $options['preview']=$attempt&&!empty($attempt->preview);
   if($options['preview'])$hook->add_html('<meta name="l10-prova-preview" content="1">');
   $PAGE->requires->js_call_amd('mod_lumen10prova/exam_modal','enhance',[(int)$PAGE->context->id,$options]);
   if(!empty($p->exitlock)&&$aid&&!$options['preview']&&$PAGE->url->get_path()==='/mod/quiz/attempt.php'){
    $owned=$DB->get_record('quiz_attempts',['id'=>$aid,'quiz'=>$p->quizid,'userid'=>$GLOBALS['USER']->id,'preview'=>0]);
    if($owned&&in_array($owned->state,['inprogress','overdue'],true))$PAGE->requires->js_call_amd('mod_lumen10prova/source_marks','init',[source_marks::page(\mod_quiz\quiz_attempt::create($aid),(int)($PAGE->url->get_param('page')??0))]);
   }
   if($options['preview']&&$PAGE->url->get_path()==='/mod/quiz/attempt.php'){
    $PAGE->requires->js_call_amd('mod_lumen10prova/room','attempt',[(int)$p->id,$aid,
     ['monitor'=>false,'logevents'=>false,'preview'=>true,'fullscreen'=>(bool)$p->fullscreen,'blockcopy'=>(bool)$p->blockcopy,'alerts'=>[]]]);
   }
  }
 }
 public static function course_updated(\core_courseformat\hook\after_course_content_updated $hook): void {
  global $DB;
  if(!get_config('mod_lumen10prova','version'))return;
  foreach($DB->get_records('lumen10prova',['course'=>$hook->course->id])as$p)engine::sync_visibility($p);
 }
}
