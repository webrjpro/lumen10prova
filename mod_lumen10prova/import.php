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
 * Import.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
$id=required_param('id',PARAM_INT);
[$p,$cm,$course,$context]=\mod_lumen10prova\ui::load($id);
require_capability('mod/lumen10prova:manage',$context);
$qcm=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);
require_capability('mod/quiz:manage',context_module::instance($qcm->id));
if(optional_param('template',0,PARAM_BOOL)) {
 header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="exam-template.csv"');
 $f=fopen('php://output','w'); fwrite($f,"\xEF\xBB\xBF"); fputcsv($f,\mod_lumen10prova\question_import::HEADERS,';','"','');
 fputcsv($f,['essay',get_string('samplequestionname','mod_lumen10prova'),get_string('samplequestiontext','mod_lumen10prova'),'','','','','','','','2'],';','"','');
 fclose($f); exit;
}
if(\mod_lumen10prova\engine::has_attempts($p)) throw new moodle_exception('lockedsettings','mod_lumen10prova');
// Discard the legacy session payload. New sessions retain only an opaque preview token.
unset($SESSION->l10prova_import);
$token=$SESSION->l10prova_importtokens[$p->id]??'';
if($_SERVER['REQUEST_METHOD']==='POST') {
 require_sesskey(); $action=required_param('action',PARAM_ALPHA);
 if($action==='preview') {
  $upload=$_FILES['questions']??null;
  if(!$upload||$upload['error']!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name'])||$upload['size']>5*1024*1024) {
   throw new moodle_exception('importinvalid','mod_lumen10prova','',get_string('importfile','mod_lumen10prova'));
  }
  $rows=\mod_lumen10prova\question_import::parse(file_get_contents($upload['tmp_name']),clean_param($upload['name'],PARAM_FILE));
  if($token)\mod_lumen10prova\import_preview::delete($token);
  $token=\mod_lumen10prova\import_preview::store((int)$p->id,$rows);
  $SESSION->l10prova_importtokens[$p->id]=$token;
 } else if(in_array($action,['confirm','cancel'],true)) {
  $submitted=required_param('token',PARAM_ALPHANUM);
  if(!$token||!hash_equals($token,$submitted))throw new moodle_exception('importinvalid','mod_lumen10prova','',get_string('importexpired','mod_lumen10prova'));
  $lock=\core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('preview-'.$USER->id.'-'.$token,10);
  if(!$lock)throw new moodle_exception('invalidrequest');
  try {
   $pending=\mod_lumen10prova\import_preview::get((int)$p->id,$token);
   if(!$pending)throw new moodle_exception('importinvalid','mod_lumen10prova','',get_string('importexpired','mod_lumen10prova'));
   if($action==='confirm')$count=\mod_lumen10prova\question_import::commit($p,$pending['rows']);
   \mod_lumen10prova\import_preview::delete($token); unset($SESSION->l10prova_importtokens[$p->id]); $token='';
  } finally { $lock->release(); }
  if($action==='confirm')redirect(new moodle_url('/mod/quiz/edit.php',['cmid'=>$qcm->id]),get_string('importsuccess','mod_lumen10prova',$count));
 } else { throw new moodle_exception('invalidrequest'); }
}
$pending=$token?\mod_lumen10prova\import_preview::get((int)$p->id,$token):null;
$rows=[];
foreach($pending['rows']??[] as $row) {
 $choices=[]; foreach(['a','b','c','d','e']as$k)if($row['alternativa_'.$k]!=='')$choices[]=['letter'=>strtoupper($k),'text'=>$row['alternativa_'.$k]];
 $rows[]=['type'=>$row['tipo'],'name'=>$row['nome'],'question'=>$row['enunciado'],'choices'=>$choices,
  'answer'=>$row['resposta']?:get_string('humangrading','mod_lumen10prova'),'marks'=>$row['pontos']];
}
\mod_lumen10prova\ui::header($p,$cm,$course,'import');
\mod_lumen10prova\ui::render('import',['id'=>$id,'sesskey'=>sesskey(),'token'=>$token,'haspreview'=>(bool)$pending,
 'previewtitle'=>get_string('previewcount','mod_lumen10prova',count($rows)),'rows'=>$rows,
 'templateurl'=>(new moodle_url('/mod/lumen10prova/import.php',['id'=>$id,'template'=>1]))->out(false),
 'nativeurl'=>(new moodle_url('/mod/quiz/edit.php',['cmid'=>$qcm->id]))->out(false)]);
\mod_lumen10prova\ui::footer();
