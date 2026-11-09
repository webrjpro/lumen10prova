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
 * Authoring test.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
final class authoring_test extends \advanced_testcase {
 public function test_institution_logo_uses_file_api_and_refuses_disguised_content():void {
  global $DB,$USER;$this->resetAfterTest();$this->setAdminUser();$g=$this->getDataGenerator();$c=$g->create_course();$a=$g->create_module('lumen10prova',['course'=>$c->id]);$p=$DB->get_record('lumen10prova',['id'=>$a->id]);$context=\context_user::instance($USER->id);$draft=file_get_unused_draft_itemid();$fs=get_file_storage();
  $record=['contextid'=>$context->id,'component'=>'user','filearea'=>'draft','itemid'=>$draft,'filepath'=>'/','filename'=>'logo.png'];$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jQ1sAAAAASUVORK5CYII=');$fs->create_file_from_string($record,$png);$p->examlogo=$draft;appearance::save($p);$stored=$fs->get_file(engine::context($p)->id,'mod_lumen10prova','examlogo',0,'/','logo.png');$this->assertNotFalse($stored);$this->assertSame($png,$stored->get_content());
  $draft=file_get_unused_draft_itemid();$record['itemid']=$draft;$fs->create_file_from_string($record,'This is not a real image.');$p->examlogo=$draft;$this->expectException(\moodle_exception::class);appearance::save($p);
 }
 private function csv(array $rows):string {$f=fopen('php://temp','w+');fputcsv($f,question_import::HEADERS,';','"','');foreach($rows as$row)fputcsv($f,$row,';','"','');rewind($f);$text=stream_get_contents($f);fclose($f);return $text;}
 private function rows():array{return [
  ['multichoice','Soma','Quanto é 2 + 2?','4','3','5','','','A','Retome a aula.','1'],
  ['truefalse','Verdadeiro','2 + 2 é 4?','','','','','','V','','1'],
  ['shortanswer','Capital','Capital do Brasil?','','','','','','Brasília','','1']];}
 public function test_native_bank_import_grade_and_student_sheet_do_not_expose_answer_key():void {
  global $DB,$CFG;$this->resetAfterTest();$this->setAdminUser();require_once($CFG->dirroot.'/mod/quiz/locallib.php');$g=$this->getDataGenerator();$course=$g->create_course();$student=$g->create_user();$g->enrol_user($student->id,$course->id,'student');$activity=$g->create_module('lumen10prova',['course'=>$course->id,'name'=>'Planilha sintética','shufflequestions'=>0,'shuffleanswers'=>0,'releasepolicy'=>'immediate']);$p=$DB->get_record('lumen10prova',['id'=>$activity->id]);$rows=question_import::parse($this->csv($this->rows()),'prova.csv');$this->assertSame(3,question_import::commit($p,$rows));$this->assertSame(3,$DB->count_records('quiz_slots',['quizid'=>$p->quizid]));
  $this->setUser($student);$qg=$g->get_plugin_generator('mod_quiz');$a=$qg->create_attempt($p->quizid,$student->id);$qg->submit_responses($a->id,[1=>'4',2=>get_string('true','qtype_truefalse'),3=>'Brasília'],false,false);$sheet=appearance::responses(\mod_quiz\quiz_attempt::create($a->id));$this->assertSame(['A'],$sheet['rows'][0]['selected']);$this->assertFalse($sheet['rows'][0]['flagged']);$this->assertSame([get_string('true','qtype_truefalse')],$sheet['rows'][1]['selected']);$this->assertStringNotContainsString('fraction',json_encode($sheet));$this->assertStringNotContainsString('rightanswer',json_encode($sheet));
  $qg->submit_responses($a->id,[1=>'4',2=>get_string('true','qtype_truefalse'),3=>'Brasília'],false,true);$this->assertEqualsWithDelta(3,(float)$DB->get_field('quiz_attempts','sumgrades',['id'=>$a->id]),0.0001);$this->assertEqualsWithDelta(10,(float)$DB->get_field('quiz_grades','grade',['quiz'=>$p->quizid,'userid'=>$student->id]),0.0001);
 }
 public function test_import_is_locked_after_a_real_attempt():void {
  global $DB;$this->resetAfterTest();$this->setAdminUser();$g=$this->getDataGenerator();$c=$g->create_course();$u=$g->create_user();$g->enrol_user($u->id,$c->id,'student');$a=$g->create_module('lumen10prova',['course'=>$c->id]);$p=$DB->get_record('lumen10prova',['id'=>$a->id]);$rows=question_import::parse($this->csv([$this->rows()[0]]),'exam.csv');question_import::commit($p,$rows);$this->setUser($u);$g->get_plugin_generator('mod_quiz')->create_attempt($p->quizid,$u->id);$this->setAdminUser();$this->expectException(\moodle_exception::class);question_import::commit($p,$rows);
 }
 public function test_invalid_answer_is_refused():void {
  $this->resetAfterTest();$rows=$this->rows();$rows[0][8]='E';$this->expectException(\moodle_exception::class);question_import::parse($this->csv($rows),'exam.csv');
 }
 public function test_xlsx_inline_strings_and_formula_rejection():void {
  $this->resetAfterTest();$rows=array_merge([question_import::HEADERS],$this->rows());$xml='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
  foreach($rows as$i=>$row){$xml.='<row r="'.($i+1).'">';foreach($row as$j=>$value)$xml.='<c r="'.chr(65+$j).($i+1).'" t="inlineStr"><is><t>'.htmlspecialchars($value,ENT_XML1,'UTF-8').'</t></is></c>';$xml.='</row>';}$xml.='</sheetData></worksheet>';
  $file=make_request_directory().'/exam.xlsx';$zip=new \ZipArchive();$zip->open($file,\ZipArchive::CREATE);$zip->addFromString('xl/workbook.xml','<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Prova" sheetId="1" r:id="rId1"/></sheets></workbook>');$zip->addFromString('xl/_rels/workbook.xml.rels','<Relationships><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>');$zip->addFromString('xl/worksheets/sheet1.xml',$xml);$zip->close();$parsed=question_import::parse(file_get_contents($file),'exam.xlsx');$this->assertCount(3,$parsed);$this->assertSame('Brasília',$parsed[2]['resposta']);
  $zip->open($file);$zip->addFromString('xl/worksheets/sheet1.xml',str_replace('<sheetData>','<sheetData><row r="99"><c r="A99"><f>1+1</f><v>2</v></c></row>',$xml));$zip->close();$this->expectException(\moodle_exception::class);question_import::parse(file_get_contents($file),'exam.xlsx');
 }
 public function test_essay_is_native_manual_grading():void {
  global $DB;$this->resetAfterTest();$this->setAdminUser();$g=$this->getDataGenerator();$c=$g->create_course();$a=$g->create_module('lumen10prova',['course'=>$c->id]);$p=$DB->get_record('lumen10prova',['id'=>$a->id]);$row=['essay','Reflexão','Explique a situação.','','','','','','','','2'];question_import::commit($p,question_import::parse($this->csv([$row]),'essay.csv'));$this->assertSame(1,$DB->count_records('quiz_slots',['quizid'=>$p->quizid]));$this->assertSame(1,$DB->count_records('qtype_essay_options'));
 }
}
