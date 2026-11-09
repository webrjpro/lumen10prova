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
 * Backup test.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/backup/tests/backup_restore_base_testcase.php');
final class backup_test extends \core_backup_backup_restore_base_testcase {
 public function test_course_import_relinks_native_quiz_and_disables_ai():void {
  global $DB;$g=$this->getDataGenerator();$c=$g->create_course();$a=$g->create_module('lumen10prova',['course'=>$c->id,'aienabled'=>1,'codeenabled'=>1]);
  $this->setAdminUser();$p=$DB->get_record('lumen10prova',['id'=>$a->id]);question_import::commit($p,[['tipo'=>'truefalse','nome'=>'Questão de backup','enunciado'=>'O backup preserva questões?','resposta'=>'V','pontos'=>'1']]);
  $image=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jBz0AAAAASUVORK5CYII=');$file=get_file_storage()->create_file_from_string(['contextid'=>engine::context($p)->id,'component'=>'mod_lumen10prova','filearea'=>'examlogo','itemid'=>0,'filepath'=>'/','filename'=>'logo.png'],$image);
  $u=$g->create_user();$g->enrol_user($u->id,$c->id,'student');engine::gate($a,$u->id);
  $DB->set_field('lumen10prova','codehash',password_hash('123456',PASSWORD_DEFAULT),['id'=>$a->id]);
  $this->setAdminUser();$dest=$g->create_course();$this->perform_import($c,$dest);$copy=$DB->get_record('lumen10prova',['course'=>$dest->id],'*',MUST_EXIST);
  $this->assertSame($a->name,$copy->name);$this->assertSame(0,(int)$copy->aienabled);$this->assertSame('',$copy->codehash);$this->assertNotSame((int)$a->quizid,(int)$copy->quizid);
  $this->assertTrue($DB->record_exists('quiz',['id'=>$copy->quizid,'course'=>$dest->id]));$this->assertSame(1,$DB->count_records('quiz',['course'=>$dest->id]));$this->assertSame(1,$DB->count_records('quiz_slots',['quizid'=>$copy->quizid]));
  $files=get_file_storage()->get_area_files(engine::context($copy)->id,'mod_lumen10prova','examlogo',0,'id',false);$this->assertCount(1,$files);$this->assertSame($file->get_contenthash(),reset($files)->get_contenthash());
  $this->assertSame(0,$DB->count_records('lumen10prova_gate',['provaid'=>$copy->id]));$this->assertTrue($DB->record_exists('lumen10prova_gate',['provaid'=>$a->id,'userid'=>$u->id]));
 }
}
