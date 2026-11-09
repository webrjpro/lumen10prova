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
 * Visibility test.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

final class visibility_test extends \advanced_testcase {
 private function fixture(array $settings=[]): array {
  global $DB,$CFG;
  $this->resetAfterTest();$this->setAdminUser();
  require_once($CFG->dirroot.'/mod/quiz/locallib.php');
  $g=$this->getDataGenerator();$c=$g->create_course();$s=$g->create_user();$g->enrol_user($s->id,$c->id,'student');
  $a=$g->create_module('lumen10prova',array_merge(['course'=>$c->id,'releasepolicy'=>'immediate','monitorenabled'=>0],$settings));
  $p=$DB->get_record('lumen10prova',['id'=>$a->id]);
  return [$p,$c,$s,get_coursemodule_from_instance('lumen10prova',$p->id,$c->id),get_coursemodule_from_instance('quiz',$p->quizid,$c->id)];
 }
 public function test_hidden_creation_does_not_leave_an_accessible_internal_quiz(): void {
  [$p,$c,$s,$own,$quiz]=$this->fixture(['visible'=>0]);
  $this->assertSame(0,(int)$quiz->visible);
  $this->assertSame(0,(int)$quiz->visibleoncoursepage);
  $this->setUser($s);$info=get_fast_modinfo($c);
  $this->assertFalse($info->get_cm($own->id)->uservisible);
  $this->assertFalse($info->get_cm($quiz->id)->uservisible);
 }
 public function test_native_course_hide_show_updates_quiz_and_grade_visibility(): void {
  global $DB;
  [$p,$c,$s,$own,$quiz]=$this->fixture();
  $GLOBALS['PAGE']->set_course($c);
  $actions=new \core_courseformat\stateactions();$updates=new \core_courseformat\stateupdates(course_get_format($c));
  $actions->cm_hide($updates,$c,[$own->id]);
  $this->assertSame(0,(int)$DB->get_field('course_modules','visible',['id'=>$quiz->id]));
  $this->assertSame(1,(int)$DB->get_field('grade_items','hidden',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]));
  $actions->cm_show($updates,$c,[$own->id]);
  $this->assertSame(1,(int)$DB->get_field('course_modules','visible',['id'=>$quiz->id]));
  $this->assertSame(0,(int)$DB->get_field('course_modules','visibleoncoursepage',['id'=>$quiz->id]));
  $this->assertSame(0,(int)$DB->get_field('grade_items','hidden',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]));
  $this->setUser($s);$info=get_fast_modinfo($c);
  $this->assertTrue($info->get_cm($quiz->id)->uservisible);
 }
 public function test_repair_preserves_attempt_answers_and_grade(): void {
  global $DB;
  [$p,$c,$s,$own,$quiz]=$this->fixture();
  $qg=$this->getDataGenerator()->get_plugin_generator('core_question');
  $cat=$qg->create_question_category(['contextid'=>\context_module::instance($quiz->id)->id]);
  $q=$qg->create_question('truefalse',null,['category'=>$cat->id]);
  quiz_add_quiz_question($q->id,$DB->get_record('quiz',['id'=>$p->quizid]));
  \mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->recompute_quiz_sumgrades();
  quiz_update_grades($DB->get_record('quiz',['id'=>$p->quizid]));
  $this->setUser($s);$generator=$this->getDataGenerator()->get_plugin_generator('mod_quiz');
  $a=$generator->create_attempt($p->quizid,$s->id);$generator->submit_responses($a->id,[1=>get_string('true','qtype_truefalse')],false,true);
  $before=$DB->get_record('quiz_attempts',['id'=>$a->id]);$grade=$DB->get_record('quiz_grades',['quiz'=>$p->quizid,'userid'=>$s->id]);
  $this->setAdminUser();set_coursemodule_visible($quiz->id,0,1);
  (new \mod_lumen10prova\task\sync_owned_quizzes())->execute();
  \core_courseformat\formatactions::cm((int)$c->id)->rename((int)$own->id,'QA renomeada depois da tentativa');
  $this->assertSame('QA renomeada depois da tentativa',$DB->get_field('quiz','name',['id'=>$p->quizid]));
  $this->assertSame('QA renomeada depois da tentativa',$DB->get_field('grade_items','itemname',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]));
  $this->assertEquals($before,$DB->get_record('quiz_attempts',['id'=>$a->id]));
  $this->assertEquals($grade,$DB->get_record('quiz_grades',['quiz'=>$p->quizid,'userid'=>$s->id]));
  $this->assertSame(1,(int)$DB->get_field('course_modules','visible',['id'=>$quiz->id]));
  $this->assertSame(0,(int)$DB->get_field('grade_items','hidden',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]));
  $obj=\mod_quiz\quiz_attempt::create($a->id);$this->assertSame('1',$obj->get_question_attempt(1)->get_last_qt_var('answer'));
 }
 public function test_repair_retains_visibility_to_restore_after_section_is_shown(): void {
  global $DB;
  [$p,$c,$s,$own,$quiz]=$this->fixture(['section'=>1]);
  $GLOBALS['PAGE']->set_course($c);
  course_update_section($c,$DB->get_record('course_sections',['id'=>$own->section]),['visible'=>0]);
  set_coursemodule_visible($quiz->id,1,1);
  engine::sync_visibility($p);
  $this->assertSame(0,(int)$DB->get_field('course_modules','visible',['id'=>$quiz->id]));
  $this->assertSame(1,(int)$DB->get_field('course_modules','visibleold',['id'=>$quiz->id]));
  course_update_section($c,$DB->get_record('course_sections',['id'=>$own->section]),['visible'=>1]);
  engine::sync_visibility($p);
  $this->assertSame(1,(int)$DB->get_field('course_modules','visible',['id'=>$quiz->id]));
  $this->setUser($s);$this->assertTrue(get_fast_modinfo($c)->get_cm($quiz->id)->uservisible);
 }
 public function test_native_owner_rename_updates_quiz_grade_item_and_calendar(): void {
  global $DB;
  [$p,$c,$s,$own,$quiz]=$this->fixture(['timeopen'=>time()+3600,'timeclose'=>time()+7200]);
  $GLOBALS['PAGE']->set_course($c);$name='QA prova renomeada pelo curso';
  (new \core_courseformat\local\cmactions($c))->rename((int)$own->id,$name);
  $this->assertSame($name,$DB->get_field('quiz','name',['id'=>$p->quizid]));
  $this->assertSame($name,$DB->get_field('grade_items','itemname',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]));
  $this->assertSame(1,$DB->count_records('grade_items',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]));
  $events=$DB->get_records('event',['modulename'=>'quiz','instance'=>$p->quizid]);
  $this->assertNotEmpty($events);foreach($events as$event)$this->assertStringContainsString($name,$event->name);
 }
 public function test_internal_quiz_follows_owner_when_moved_out_of_hidden_section(): void {
  global $DB;
  [$p,$c,$s,$own,$quiz]=$this->fixture(['section'=>1]);
  $GLOBALS['PAGE']->set_course($c);
  course_update_section($c,$DB->get_record('course_sections',['id'=>$own->section]),['visible'=>0]);
  course_create_sections_if_missing($c,[2]);
  $target=$DB->get_record('course_sections',['course'=>$c->id,'section'=>2],'*',MUST_EXIST);
  $owncm=get_coursemodule_from_instance('lumen10prova',$p->id,$c->id);
  if(method_exists(\core_courseformat\local\cmactions::class,'move_end_section')) {
   (new \core_courseformat\local\cmactions($c))->move_end_section((int)$owncm->id,(int)$target->id);
  } else {
   moveto_module($owncm,$target);
  }
  engine::sync_visibility($p);
  $this->assertSame((int)$target->id,(int)$DB->get_field('course_modules','section',['id'=>$quiz->id]));
  $sequence=explode(',',$DB->get_field('course_sections','sequence',['id'=>$target->id]));
  $this->assertContains((string)$quiz->id,$sequence);
  $this->assertSame(0,(int)$DB->get_field('course_modules','visibleoncoursepage',['id'=>$quiz->id]));
  $this->setUser($s);$this->assertTrue(get_fast_modinfo($c)->get_cm($quiz->id)->uservisible);
 }
 public function test_disabled_monitor_does_not_report_missing_heartbeat_as_failure(): void {
  global $DB;
  [$p,$c,$s,$own,$quiz]=$this->fixture();
  $qg=$this->getDataGenerator()->get_plugin_generator('core_question');
  $cat=$qg->create_question_category(['contextid'=>\context_module::instance($quiz->id)->id]);
  $q=$qg->create_question('truefalse',null,['category'=>$cat->id]);
  quiz_add_quiz_question($q->id,$GLOBALS['DB']->get_record('quiz',['id'=>$p->quizid]));
  \mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->recompute_quiz_sumgrades();
  $this->setUser($s);$a=$this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_attempt($p->quizid,$s->id);
  $this->setAdminUser();$live=engine::live($p,$a);$DB->set_field('lumen10prova_live','lastseen',time()-200,['id'=>$live->id]);
  $this->assertSame('inprogress',engine::status_rows($p)[0]['status']);
  $p->monitorenabled=1;$this->assertSame('unresponsive',engine::status_rows($p)[0]['status']);
 }
 public function test_upgrade_queues_repair_without_calling_course_api_during_upgrade(): void {
  global $DB,$CFG;
  [$p,$c,$s,$own,$quiz]=$this->fixture();
  set_coursemodule_visible($quiz->id,0,1);
  require_once($CFG->libdir.'/upgradelib.php');
  require_once($CFG->dirroot.'/mod/lumen10prova/db/upgrade.php');
  set_config('version',2026100800,'mod_lumen10prova');
  $previous=$CFG->upgraderunning??null;$CFG->upgraderunning=time()+300;
  try {
   $this->assertTrue(xmldb_lumen10prova_upgrade(2026100800));
   $this->assertSame('2026110900',get_config('mod_lumen10prova','version'));
   $this->assertTrue($DB->get_manager()->field_exists(new \xmldb_table('lumen10prova'),new \xmldb_field('exitlock')));
   foreach(['blockedat','blockreason','securitygeneration']as$field)$this->assertTrue($DB->get_manager()->field_exists(new \xmldb_table('lumen10prova_live'),new \xmldb_field($field)));
   $this->assertSame(0,(int)$DB->get_field('lumen10prova','exitlock',['id'=>$p->id]));
   $this->assertSame(0,(int)$DB->get_field('course_modules','visible',['id'=>$quiz->id]));
   $this->assertSame(1,(int)$DB->get_field('course_modules','visibleoncoursepage',['id'=>$quiz->id]));
   $this->assertSame(1,$DB->count_records('task_adhoc',['classname'=>'\\mod_lumen10prova\\task\\sync_owned_quizzes']));
  } finally {
   if($previous===null)unset($CFG->upgraderunning);else $CFG->upgraderunning=$previous;
  }
  $task=new \mod_lumen10prova\task\sync_owned_quizzes();$task->execute();$task->execute();
  $this->assertSame(1,(int)$DB->get_field('course_modules','visible',['id'=>$quiz->id]));
  $this->assertSame(0,(int)$DB->get_field('course_modules','visibleoncoursepage',['id'=>$quiz->id]));
  $this->assertSame(1,$DB->count_records('grade_items',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]));
 }
}
