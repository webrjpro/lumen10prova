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
 * Question import.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
/** Bounded spreadsheet parser and native Question Bank writer. No spreadsheet code executes. */
final class question_import {
 public const HEADERS=['tipo','nome','enunciado','alternativa_a','alternativa_b','alternativa_c','alternativa_d','alternativa_e','resposta','feedback','pontos'];
 private static function fail(string $key, $a = null): never {throw new \moodle_exception('importinvalid','mod_lumen10prova','',get_string($key,'mod_lumen10prova',$a));}
 public static function parse(string $bytes,string $name): array {
  if(strlen($bytes)>5*1024*1024||$bytes==='')self::fail('importerror1');
  $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
  if($ext==='csv'){$bytes=preg_replace('/^\xEF\xBB\xBF/','',$bytes);if(!mb_check_encoding($bytes,'UTF-8'))self::fail('importerror2');
   $first=strtok($bytes,"\r\n");$separator=substr_count($first,';')>substr_count($first,',')?';':',';$stream=fopen('php://temp','w+');fwrite($stream,$bytes);rewind($stream);$table=[];
   while(($row=fgetcsv($stream,0,$separator,'"',''))!==false){if(count($row)===1&&trim((string)$row[0])==='')continue;$table[]=$row;if(count($table)>201){fclose($stream);self::fail('importerror3');}}fclose($stream);
  }elseif($ext==='xlsx'){$table=self::xlsx($bytes);}else{self::fail('importerror4');}
  if(count($table)<2)self::fail('importerror5');
  $headers=array_map(fn($v)=>\core_text::strtolower(trim((string)$v)),array_shift($table));
  if(count($headers)!==count(array_unique($headers))||array_diff(self::HEADERS,$headers)||array_diff($headers,self::HEADERS))self::fail('importerror6');
  $rows=[];foreach($table as $i=>$cells){if(count($cells)>count($headers))self::fail('importerror7',($i+2));$cells=array_pad($cells,count($headers),'');$row=array_combine($headers,array_map(fn($v)=>trim((string)$v),$cells));$rows[]=self::validate_row($row,$i+2);}
  return $rows;
 }
 public static function validate_row(array $row,int $line):array {
  foreach(self::HEADERS as $key){$row[$key]=trim((string)($row[$key]??''));if(\core_text::strlen($row[$key])>12000)self::fail('importerror8',$line);}
  $aliases=['multichoice'=>'multichoice','multipla'=>'multichoice','múltipla'=>'multichoice','truefalse'=>'truefalse','verdadeiro_falso'=>'truefalse','essay'=>'essay','dissertativa'=>'essay','shortanswer'=>'shortanswer','curta'=>'shortanswer'];$type=$aliases[\core_text::strtolower($row['tipo'])]??'';
  if(!$type||$row['enunciado']==='')self::fail('importerror9',$line);$row['tipo']=$type;$row['nome']=$row['nome']?:get_string('questionnumber','mod_lumen10prova',$line-1);if(\core_text::strlen($row['nome'])>255)self::fail('importerror10',$line);
  $mark=str_replace(',','.',$row['pontos']?:'1');if(!is_numeric($mark)||!is_finite((float)$mark)||(float)$mark<=0||(float)$mark>1000)self::fail('importerror11',$line);$row['pontos']=(float)$mark;
  if($type==='multichoice'){$row['resposta']=strtoupper($row['resposta']);$answers=array_filter(array_map(fn($k)=>$row['alternativa_'.$k],['a','b','c','d','e']),fn($v)=>$v!=='');if(count($answers)<2||!preg_match('/^[A-E]$/',$row['resposta'])||$row['alternativa_'.strtolower($row['resposta'])]==='')self::fail('importerror12',$line);}
  if($type==='truefalse'){$answer=\core_text::strtolower($row['resposta']);if(!in_array($answer,['v','f','1','0','true','false','verdadeiro','falso'],true))self::fail('importerror13',$line);$row['resposta']=in_array($answer,['v','1','true','verdadeiro'],true)?'1':'0';}
  if($type==='shortanswer'&&$row['resposta']==='')self::fail('importerror14',$line);
  if($type==='essay'&&$row['resposta']!=='')self::fail('importerror15',$line);
  return $row;
 }
 private static function xml(string $source):\DOMDocument {
  if(preg_match('/<!\s*(?:DOCTYPE|ENTITY)/i',$source))self::fail('importerror16');$doc=new \DOMDocument();$previous=libxml_use_internal_errors(true);$ok=$doc->loadXML($source,LIBXML_NONET|LIBXML_NOBLANKS);libxml_clear_errors();libxml_use_internal_errors($previous);if(!$ok)self::fail('importerror17');return $doc;
 }
 private static function xlsx(string $bytes):array {
  $file=tempnam(sys_get_temp_dir(),'l10exam');file_put_contents($file,$bytes);$zip=new \ZipArchive();$opened=false;
  try{if($zip->open($file)!==true)self::fail('importerror18');$opened=true;$total=0;if($zip->numFiles>300)self::fail('importerror19');
   for($i=0;$i<$zip->numFiles;$i++){$stat=$zip->statIndex($i);$total+=$stat['size'];if($stat['size']>20*1024*1024||$total>20*1024*1024||preg_match('~vbaProject|externalLinks|embeddings~i',$stat['name']))self::fail('importerror20');}
   $book=$zip->getFromName('xl/workbook.xml');$rels=$zip->getFromName('xl/_rels/workbook.xml.rels');if($book===false||$rels===false)self::fail('importerror21');$doc=self::xml($book);$sheets=$doc->getElementsByTagNameNS('*','sheet');if(!$sheets->length)self::fail('importerror22');$id=$sheets->item(0)->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships','id');$target='';
   foreach(self::xml($rels)->getElementsByTagNameNS('*','Relationship')as$rel){if($rel->getAttribute('TargetMode')==='External')self::fail('importerror23');if($rel->getAttribute('Id')===$id)$target=$rel->getAttribute('Target');}
   $target=ltrim($target,'/');if(str_starts_with($target,'xl/'))$part=$target;else$part='xl/'.$target;if(str_contains($part,'..')||!preg_match('~^xl/worksheets/[^/]+\.xml$~',$part))self::fail('importerror24');$source=$zip->getFromName($part);if($source===false)self::fail('importerror25');$doc=self::xml($source);if($doc->getElementsByTagNameNS('*','f')->length)self::fail('importerror26');
   $strings=[];$shared=$zip->getFromName('xl/sharedStrings.xml');if($shared!==false){foreach(self::xml($shared)->getElementsByTagNameNS('*','si')as$si){$s='';foreach($si->getElementsByTagNameNS('*','t')as$t)$s.=$t->textContent;$strings[]=$s;if(count($strings)>10000)self::fail('importerror19');}}
   $rows=[];foreach($doc->getElementsByTagNameNS('*','row')as$row){$cells=[];foreach($row->getElementsByTagNameNS('*','c')as$c){if(!preg_match('/^([A-Z]{1,2})[0-9]+$/',$c->getAttribute('r'),$m))self::fail('importerror27');$column=0;foreach(str_split($m[1])as$letter)$column=$column*26+ord($letter)-64;$column--;if($column>=count(self::HEADERS))self::fail('importerror28');$value=$c->getElementsByTagNameNS('*','v')->item(0)?->textContent??'';$type=$c->getAttribute('t');if($type==='s'){if(!ctype_digit($value)||!array_key_exists((int)$value,$strings))self::fail('importerror29');$value=$strings[(int)$value];}elseif($type==='inlineStr'){$value='';foreach($c->getElementsByTagNameNS('*','t')as$t)$value.=$t->textContent;}elseif(in_array($type,['e','b'],true))self::fail('importerror30');$cells[$column]=$value;}
    if(!$cells)continue;$expanded=array_fill(0,count(self::HEADERS),'');foreach($cells as$k=>$v)$expanded[$k]=$v;if(implode('',$expanded)==='')continue;$rows[]=$expanded;if(count($rows)>201)self::fail('importerror31');}return $rows;
  }finally{if($opened)$zip->close();unlink($file);}
 }
 /** Requires teacher and native question permissions; adds to existing exam, never deletes questions. */
 public static function commit(\stdClass $p,array $rows):int {
  global $DB,$CFG;require_once($CFG->dirroot.'/mod/quiz/locallib.php');require_once($CFG->dirroot.'/course/modlib.php');require_capability('mod/lumen10prova:manage',engine::context($p));$qcm=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);require_capability('mod/quiz:manage',\context_module::instance($qcm->id));
  if(!$rows||count($rows)>200)self::fail('importerror32');$lock=\core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('import-'.$p->id,10);if(!$lock)self::fail('importerror33');
  try{if(engine::has_attempts($p))throw new \moodle_exception('lockedsettings','mod_lumen10prova');$course=get_course($p->course);$cm=\core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course,true);$context=\context_module::instance($cm->id);require_capability('moodle/question:add',$context);$tx=$DB->start_delegated_transaction();
   $top=question_get_top_category($context->id,true);
   $cat=(object)['contextid'=>$context->id,'name'=>\core_text::substr(get_string('importcategory','mod_lumen10prova',$p->name),0,255),'info'=>'','infoformat'=>FORMAT_PLAIN,'stamp'=>make_unique_id_code(),'parent'=>$top->id,'sortorder'=>999];$cat->id=$DB->insert_record('question_categories',$cat);$quiz=$DB->get_record('quiz',['id'=>$p->quizid],'*',MUST_EXIST);
   foreach($rows as$i=>$row){$row=self::validate_row($row,$i+2);$editor=fn($text)=>['text'=>$text,'format'=>FORMAT_PLAIN,'itemid'=>0];$form=(object)['category'=>$cat->id.','.$context->id,'name'=>$row['nome'],'questiontext'=>$editor($row['enunciado']),'generalfeedback'=>$editor($row['feedback']),'defaultmark'=>$row['pontos'],'penalty'=>0,'status'=>'ready'];$type=$row['tipo'];
    if($type==='multichoice'){$form->single=1;$form->shuffleanswers=(int)$p->shuffleanswers;$form->answernumbering='abc';$form->showstandardinstruction=1;$form->correctfeedback=$editor('');$form->partiallycorrectfeedback=$editor('');$form->incorrectfeedback=$editor('');$form->shownumcorrect=0;$form->answer=[];$form->fraction=[];$form->feedback=[];
     foreach(['a','b','c','d','e']as$key){if($row['alternativa_'.$key]==='')continue;$form->answer[]=$editor($row['alternativa_'.$key]);$form->fraction[]=strtolower($row['resposta'])===$key?1:0;$form->feedback[]=$editor('');}}
    elseif($type==='truefalse'){$form->correctanswer=(int)$row['resposta'];$form->feedbacktrue=$editor('');$form->feedbackfalse=$editor('');}
    elseif($type==='shortanswer'){$form->usecase=0;$form->answer=[$row['resposta']];$form->fraction=[1];$form->feedback=[$editor('')];}
    else{$form->responseformat='editor';$form->responserequired=1;$form->responsefieldlines=10;$form->attachments=0;$form->attachmentsrequired=0;$form->graderinfo=$editor('');$form->responsetemplate=$editor('');}
    $question=\question_bank::get_qtype($type)->save_question((object)['qtype'=>$type,'context'=>$context],$form);quiz_add_quiz_question($question->id,$quiz,0,$row['pontos']);
   }
   quiz_repaginate_questions($p->quizid,$p->navigation==='blocks'?$p->blocksize:1);\mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->recompute_quiz_sumgrades();quiz_update_grades($DB->get_record('quiz',['id'=>$p->quizid]));engine::audit($p,0,0,'questions_imported','count='.count($rows));$tx->allow_commit();rebuild_course_cache($p->course,true);return count($rows);
  }finally{$lock->release();}
 }
}
