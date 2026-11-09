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
 * Appearance.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
final class appearance {
 public static function options():array{return ['subdirs'=>false,'maxbytes'=>5*1024*1024,'maxfiles'=>1,'accepted_types'=>['.png','.jpg','.jpeg','.webp']];}
 public static function save(\stdClass $p):void {
  global $USER;if(!isset($p->examlogo)&&!isset($p->examwatermark))return;$context=!empty($p->coursemodule)?\context_module::instance($p->coursemodule):engine::context($p);
  foreach(['examlogo','examwatermark']as$area){if(!isset($p->$area))continue;$draft=(int)$p->$area;if($draft){$files=get_file_storage()->get_area_files(\context_user::instance($USER->id)->id,'user','draft',$draft,'id',false);
   if(count($files)>1)throw new \moodle_exception('invalidimage','mod_lumen10prova');foreach($files as$file){$size=@getimagesizefromstring($file->get_content());if($file->get_filesize()>5*1024*1024||!$size||!in_array($size['mime'],['image/png','image/jpeg','image/webp'],true)||$size[0]>8000||$size[1]>8000)throw new \moodle_exception('invalidimage','mod_lumen10prova');}}
   file_save_draft_area_files($draft,$context->id,'mod_lumen10prova',$area,0,self::options());
  }
 }
 private static function image(\stdClass $p,string $area):string {$context=engine::context($p);$files=get_file_storage()->get_area_files($context->id,'mod_lumen10prova',$area,0,'id',false);if(!$files)return '';$file=reset($files);return \moodle_url::make_pluginfile_url($context->id,'mod_lumen10prova',$area,0,$file->get_filepath(),$file->get_filename())->out(false);}
 public static function settings(\stdClass $p):array {global $OUTPUT,$USER;$logo=self::image($p,'examlogo');if(!$logo){$default=$OUTPUT->get_logo_url();$logo=$default?$default->out(false):'';}
  return ['id'=>(int)$p->id,'cmid'=>(int)engine::context($p)->instanceid,'exitlock'=>!empty($p->exitlock),'timelimit'=>(int)$p->timelimit,'title'=>format_string($p->name),'course'=>format_string(get_course($p->course)->fullname),'student'=>fullname($USER),'logo'=>$logo,'watermark'=>self::image($p,'examwatermark')?:$logo,'opacity'=>(float)($p->watermarkopacity??0.06),'paper'=>(bool)($p->paperstyle??1),'answersheet'=>(bool)($p->answersheetenabled??1)];
 }
 public static function responses(\mod_quiz\quiz_attempt $obj):array {
  $rows=[];$slots=$obj->get_slots();foreach(array_slice($slots,0,200)as$slot){$qa=$obj->get_question_attempt($slot);$q=$qa->get_question();$type=$q->qtype->name();$selected=[];$choices=[];
   if($type==='multichoice'){foreach($q->get_order($qa)as$index=>$unused){$letter=$index<26?chr(65+$index):(string)($index+1);$choices[]=$letter;if($q->is_choice_selected($q->get_response($qa),$index))$selected[]=$letter;}}
   elseif($type==='truefalse'){$choices=[get_string('true','qtype_truefalse'),get_string('false','qtype_truefalse')];$value=$qa->get_last_qt_var('answer');if($value!==null&&$value!=='')$selected[]=(string)$value==='1'?$choices[0]:$choices[1];}
   $answered=$qa->get_state()->is_active()?$qa->get_response_summary()!==null&&$qa->get_state()!==\question_state::$todo:$qa->get_state()->is_finished();
   $rows[]=['slot'=>(int)$slot,'domid'=>'question-'.$obj->get_uniqueid().'-'.$slot,'number'=>(string)$obj->get_question_number($slot),'page'=>(int)$obj->get_question_page($slot),'type'=>$type,'selected'=>$selected,'choices'=>$choices,'answered'=>$answered,'flagged'=>(bool)$qa->is_flagged()];
  }return ['total'=>count($slots),'rows'=>$rows];
 }
}
