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
 * Controls test.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

final class controls_test extends \advanced_testcase {
 private function fixture(array $settings=[]): array {
  global $DB,$CFG;
  $this->resetAfterTest();$this->setAdminUser();require_once($CFG->dirroot.'/mod/quiz/locallib.php');
  $g=$this->getDataGenerator();$c=$g->create_course();$s=$g->create_user();$g->enrol_user($s->id,$c->id,'student');
  $a=$g->create_module('lumen10prova',array_merge(['course'=>$c->id],$settings));
  return [$DB->get_record('lumen10prova',['id'=>$a->id]),$c,$s];
 }
 public function test_initial_grade_window_and_password_reach_native_quiz(): void {
  global $DB;
  $open=time()+3600;$close=$open+7200;
  [$p,$c,$s]=$this->fixture(['grade'=>20,'gradepass'=>12,'timeopen'=>$open,'timeclose'=>$close,'exampassword'=>'synthetic-exam','navigation'=>'blocks','blocksize'=>5]);
  $quiz=$DB->get_record('quiz',['id'=>$p->quizid]);
  $item=$DB->get_record('grade_items',['itemmodule'=>'quiz','iteminstance'=>$p->quizid]);
  $this->assertEquals(20,(float)$item->grademax);$this->assertEquals(12,(float)$item->gradepass);
  $this->assertSame($open,(int)$quiz->timeopen);$this->assertSame($close,(int)$quiz->timeclose);
  $this->assertSame('synthetic-exam',$quiz->password);$this->assertSame(5,(int)$quiz->questionsperpage);$this->assertSame('seq',$quiz->navmethod);
  $this->setUser($s);$this->assertNotEmpty(\mod_quiz\quiz_settings::create($p->quizid)->get_access_manager(time())->prevent_access());
 }
 public function test_rotating_code_requires_current_code_and_locks_repeated_errors(): void {
  global $DB,$SESSION;
  [$p,$c,$s]=$this->fixture(['codeenabled'=>1,'manualstart'=>1]);$this->setUser($s);
  $rule=\quizaccess_lumen10prova::make(\mod_quiz\quiz_settings::create($p->quizid),time(),false);
  $this->assertNotEmpty($rule->prevent_access());
  $this->setAdminUser();engine::release($p,[$s->id]);
  $hash=password_hash('123456',PASSWORD_DEFAULT);$DB->set_field('lumen10prova','codehash',$hash,['id'=>$p->id]);
  $this->setUser($s);$rule=\quizaccess_lumen10prova::make(\mod_quiz\quiz_settings::create($p->quizid),time(),false);
  $this->assertFalse($rule->prevent_access());$this->assertTrue($rule->is_preflight_check_required(null));
  for($i=0;$i<5;$i++)$this->assertArrayHasKey('l10code',$rule->validate_preflight_check(['l10code'=>'000000'],[],[],null));
  $this->assertArrayHasKey('l10code',$rule->validate_preflight_check(['l10code'=>'123456'],[],[],null));
  $gate=engine::gate($p,$s->id);$DB->set_field('lumen10prova_gate','codelocked',0,['id'=>$gate->id]);
  $this->assertSame([],$rule->validate_preflight_check(['l10code'=>'123456'],[],[],null));
  $rule->notify_preflight_check_passed(null);$this->assertFalse($rule->is_preflight_check_required(null));
  $DB->set_field('lumen10prova','codehash',password_hash('654321',PASSWORD_DEFAULT),['id'=>$p->id]);
  $rule=\quizaccess_lumen10prova::make(\mod_quiz\quiz_settings::create($p->quizid),time(),false);
  $this->assertTrue($rule->is_preflight_check_required(null));
  $this->assertArrayHasKey('l10code',$rule->validate_preflight_check(['l10code'=>'123456'],[],[],null));
  $this->assertSame([],$rule->validate_preflight_check(['l10code'=>'654321'],[],[],null));
 }
}
