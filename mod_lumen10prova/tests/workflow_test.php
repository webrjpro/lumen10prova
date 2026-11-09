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
 * Workflow test.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
final class workflow_test extends \advanced_testcase {
 private function fixture(array $settings=[]):array {
  global $CFG,$DB;$this->resetAfterTest();$this->setAdminUser();
  require_once($CFG->dirroot.'/mod/quiz/locallib.php');require_once($CFG->dirroot.'/mod/quiz/lib.php');require_once($CFG->libdir.'/gradelib.php');
  $g=$this->getDataGenerator();$c=$g->create_course(['enablecompletion'=>1]);$s=$g->create_user();$t=$g->create_user();
  $g->enrol_user($s->id,$c->id,'student');$g->enrol_user($t->id,$c->id,'editingteacher');
  $a=$g->create_module('lumen10prova',array_merge(['course'=>$c->id,'name'=>'Prova sintética','timeclose'=>time()+3600,'timelimit'=>120,'completion'=>2,'completionpassed'=>1],$settings));
  $p=$DB->get_record('lumen10prova',['id'=>$a->id],'*',MUST_EXIST);return [$p,$c,$s,$t];
 }
 private function questions(\stdClass $p,string $type='truefalse',int $count=1):void {
  $qg=$this->getDataGenerator()->get_plugin_generator('core_question');
  $bank=$this->getDataGenerator()->create_module('qbank',['course'=>$p->course]);$cat=$qg->create_question_category(['contextid'=>\context_module::instance($bank->cmid)->id]);
  for($i=0;$i<$count;$i++){$q=$qg->create_question($type,null,['category'=>$cat->id]);quiz_add_quiz_question($q->id,$GLOBALS['DB']->get_record('quiz',['id'=>$p->quizid]));}
  \mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->recompute_quiz_sumgrades();
  quiz_update_grades($GLOBALS['DB']->get_record('quiz',['id'=>$p->quizid]));
 }
 public function test_creates_single_native_grade_and_no_duplicate_course_weight():void {
  global $DB;[$p,$c,$s]=$this->fixture(['releasepolicy'=>'immediate']);$this->questions($p);$this->setUser($s);
  $qg=$this->getDataGenerator()->get_plugin_generator('mod_quiz');$a=$qg->create_attempt($p->quizid,$s->id);$qg->submit_responses($a->id,[1=>get_string('true','qtype_truefalse')],false,true);
  grade_regrade_final_grades($c->id);
  $items=$DB->get_records('grade_items',['courseid'=>$c->id,'itemtype'=>'mod']);$this->assertCount(1,$items);$gi=reset($items);
  $this->assertSame('quiz',$gi->itemmodule);$this->assertSame($p->name,$gi->itemname);$this->assertSame(0,(int)$gi->hidden);
  $grade=$DB->get_record('grade_grades',['itemid'=>$gi->id,'userid'=>$s->id]);$this->assertEqualsWithDelta(10,(float)$grade->finalgrade,0.0001);
  if(is_file($GLOBALS['CFG']->dirroot.'/mod/piquetcert/lib.php')) {
  require_once($GLOBALS['CFG']->dirroot.'/mod/piquetcert/lib.php');
  $cert=(object)['source_mode'=>'course','categoryid'=>0,'custom_total_hours'=>0,'exclude_keywords'=>'','selected_courses'=>''];
  $transcript=piquetcert_get_student_transcript($cert,$c->id,$s->id);$this->assertCount(1,$transcript['items']);$this->assertSame('10,00',$transcript['items'][0]->nota);
  }
  $this->assertTrue(engine::completion($p,$s->id));$this->assertTrue(engine::grade_ready($p,$s->id));
 }
 public function test_editing_maximum_grade_updates_native_gradebook():void {
  global $DB;[$p]=$this->fixture();$p->grade=20;$p->gradepass=12;engine::update_quiz($p);
  $this->assertEqualsWithDelta(20,(float)$DB->get_field('quiz','grade',['id'=>$p->quizid]),0.0001);
  $item=\grade_item::fetch(['itemtype'=>'mod','itemmodule'=>'quiz','iteminstance'=>$p->quizid,'itemnumber'=>0,'courseid'=>$p->course]);
  $this->assertEqualsWithDelta(20,(float)$item->grademax,0.0001);$this->assertEqualsWithDelta(12,(float)$item->gradepass,0.0001);
 }
 public function test_manual_start_cannot_be_bypassed_with_native_quiz_url():void {
  [$p,$c,$s,$t]=$this->fixture(['manualstart'=>1]);$this->setUser($s);
  $manager=\mod_quiz\quiz_settings::create($p->quizid)->get_access_manager(time());$this->assertNotEmpty($manager->prevent_access());$this->assertSame('waiting',engine::access_reason($p,$s->id));
  $this->setUser($t);$this->assertSame(1,engine::release($p,[$s->id]));$this->assertSame(0,engine::release($p,[$s->id]));
  $this->setUser($s);$this->assertSame('',engine::access_reason($p,$s->id));$this->assertSame([],\mod_quiz\quiz_settings::create($p->quizid)->get_access_manager(time())->prevent_access());
 }
 public function test_group_and_suspension_and_profile_restrictions():void {
  global $DB;[$p,$c,$s]=$this->fixture(['profilefields'=>'idnumber']);$DB->set_field('user','idnumber','',['id'=>$s->id]);$s->idnumber='';$this->setUser($s);$this->assertSame('needprofile',engine::access_reason($p,$s->id));
  $DB->set_field('user','idnumber','SYNTHETIC',['id'=>$s->id]);$group=$this->getDataGenerator()->create_group(['courseid'=>$c->id]);$p->groupid=$group->id;$this->assertFalse(engine::participant($p,$s->id));
  groups_add_member($group->id,$s->id);$this->assertTrue(engine::participant($p,$s->id));$DB->set_field('user','suspended',1,['id'=>$s->id]);$this->assertFalse(engine::participant($p,$s->id));
 }
 public function test_server_deadline_continues_and_synchronised_start_uses_release_epoch():void {
  [$p,$c,$s,$t]=$this->fixture(['manualstart'=>1,'syncstart'=>1]);$this->setUser($t);engine::release($p,[$s->id],time()-30);
  $a=(object)['timestart'=>time(),'userid'=>$s->id];$q=(object)['timelimit'=>120,'timeclose'=>time()+3600];$end=engine::deadline($p,$a,$q);
  $this->assertEqualsWithDelta(time()+90,$end,1);$q->timelimit=180;$this->assertEqualsWithDelta(time()+150,engine::deadline($p,$a,$q),1);
 }
 public function test_native_autosaved_answer_is_graded_on_server_expiry():void {
  global $DB;[$p,$c,$s]=$this->fixture(['releasepolicy'=>'immediate','timelimit'=>60]);$this->questions($p);$this->setUser($s);
  $qg=$this->getDataGenerator()->get_plugin_generator('mod_quiz');$a=$qg->create_attempt($p->quizid,$s->id);$qg->submit_responses($a->id,[1=>get_string('true','qtype_truefalse')],false,false);
  $DB->set_field('quiz_attempts','timestart',time()-90,['id'=>$a->id]);engine::process($p);
  $a=$DB->get_record('quiz_attempts',['id'=>$a->id]);$this->assertSame('finished',$a->state);$this->assertEqualsWithDelta(1,(float)$a->sumgrades,0.0001);
 }
 public function test_correct_answers_are_not_exposed_before_all_extended_windows_close():void {
  global $DB;[$p,$c,$s]=$this->fixture(['releasepolicy'=>'immediate','showanswers'=>1]);engine::review_policy($p);$q=$DB->get_record('quiz',['id'=>$p->quizid]);$this->assertSame(0,(int)$q->reviewrightanswer);$this->assertNotSame(0,(int)$q->reviewmarks);
  $p->timeclose=time()-1;$DB->set_field('quiz','timeclose',$p->timeclose,['id'=>$p->quizid]);$DB->insert_record('quiz_overrides',(object)['quiz'=>$p->quizid,'userid'=>$s->id,'timeclose'=>time()+600]);
  $this->assertFalse(engine::closed($p));engine::review_policy($p);$this->assertSame(0,(int)$DB->get_field('quiz','reviewrightanswer',['id'=>$p->quizid]));
  $DB->set_field('quiz_overrides','timeclose',time()-1,['quiz'=>$p->quizid]);$this->assertTrue(engine::closed($p));engine::review_policy($p);$this->assertNotSame(0,(int)$DB->get_field('quiz','reviewrightanswer',['id'=>$p->quizid]));
 }
 public function test_essay_grade_waits_for_human_and_result_release():void {
  global $DB;[$p,$c,$s]=$this->fixture(['releasepolicy'=>'manual']);$this->questions($p,'essay');$this->setUser($s);
  $qg=$this->getDataGenerator()->get_plugin_generator('mod_quiz');$a=$qg->create_attempt($p->quizid,$s->id);$qg->submit_responses($a->id,[1=>'Resposta fictícia.'],false,true);
  $a=$DB->get_record('quiz_attempts',['id'=>$a->id]);$this->assertNull($a->sumgrades);$this->assertFalse(engine::grade_ready($p,$s->id));$this->assertFalse(engine::completion($p,$s->id));
  $this->setAdminUser();$obj=\mod_quiz\quiz_attempt::create($a->id);$obj->get_question_usage()->manual_grade(1,'Revisão humana',0.8,FORMAT_PLAIN);\question_engine::save_questions_usage_by_activity($obj->get_question_usage());
  $obj=\mod_quiz\quiz_attempt::create($a->id);$obj->process_submitted_actions(time(),false,[]);\mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->recompute_all_final_grades();quiz_update_grades($DB->get_record('quiz',['id'=>$p->quizid]));
  $p->released=1;engine::review_policy($p);$this->assertTrue(engine::grade_ready($p,$s->id));$this->assertTrue(engine::completion($p,$s->id));
 }
 public function test_regulation_change_requires_fresh_acceptance():void {
  [$p,$c,$s]=$this->fixture(['regulations'=>'Regra um']);$this->setUser($s);$this->assertFalse(engine::accepted($p,$s->id));engine::record_acceptance($p,$s->id);$this->assertTrue(engine::accepted($p,$s->id));$p->regulations='Regra dois';$this->assertFalse(engine::accepted($p,$s->id));
 }
 public function test_structural_settings_and_invalid_foreign_group_are_rejected():void {
  [$p,$c]=$this->fixture();$change=clone $p;$change->timelimit++;$this->assertTrue(engine::structural_change($p,$change));
  $other=$this->getDataGenerator()->create_course();$group=$this->getDataGenerator()->create_group(['courseid'=>$other->id]);$this->assertArrayHasKey('groupid',engine::validation(['groupid'=>$group->id],$c->id));$this->assertArrayHasKey('gradepass',engine::validation(['grade'=>10,'gradepass'=>11],$c->id));
 }
 public function test_optional_ai_and_telemetry_default_off():void {
  [$p]=$this->fixture();$this->assertSame(0,(int)$p->aienabled);$this->assertSame(0,(int)$p->logevents);$this->assertFalse((bool)get_config('mod_lumen10prova','enableai'));
 }
 public function test_privacy_scoped_erasure_preserves_other_participant():void {
  global $DB;[$p,$c,$s,$t]=$this->fixture();engine::gate($p,$s->id);engine::gate($p,$t->id);engine::audit($p,$s->id,0,'privacy_test');
  $contexts=\mod_lumen10prova\privacy\provider::get_contexts_for_userid($s->id);$this->assertContains(engine::context($p)->id,array_map('intval',$contexts->get_contextids()));
  $list=new \core_privacy\local\request\approved_contextlist($s,'mod_lumen10prova',$contexts->get_contextids());\mod_lumen10prova\privacy\provider::delete_data_for_user($list);
  $this->assertFalse($DB->record_exists('lumen10prova_gate',['userid'=>$s->id]));$this->assertTrue($DB->record_exists('lumen10prova_gate',['userid'=>$t->id]));
 }
}
