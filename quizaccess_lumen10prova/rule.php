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
 * Rule.
 *
 * @package    quizaccess_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
class quizaccess_lumen10prova extends \mod_quiz\local\access_rule_base {
 private \stdClass $prova;
 public static function validate_settings_form_fields(array $errors,array $data,$files,\mod_quiz_mod_form $form) {
  global $DB;$id=(int)($form->get_current()->id??0);
  if($id&&$DB->get_manager()->table_exists('lumen10prova')&&($p=$DB->get_record('lumen10prova',['quizid'=>$id]))) {
   $errors['name']=get_string('managedsettings','quizaccess_lumen10prova');
  }
  return $errors;
 }
 public static function make(\mod_quiz\quiz_settings $quizobj,$timenow,$canignoretimelimits) {
  global $DB;if(!$DB->get_manager()->table_exists('lumen10prova'))return null;
  $p=$DB->get_record('lumen10prova',['quizid'=>$quizobj->get_quizid()]);if(!$p)return null;
  $rule=new self($quizobj,$timenow);$rule->prova=$p;return $rule;
 }
 private function manager():bool {global $USER;$context=\mod_lumen10prova\engine::context($this->prova);return has_capability('mod/lumen10prova:manage',$context,$USER)||has_capability('mod/lumen10prova:grade',$context,$USER)||has_capability('mod/lumen10prova:monitor',$context,$USER);}
 public function prevent_access(){global $USER;if($this->manager())return false;$why=\mod_lumen10prova\engine::access_reason($this->prova,(int)$USER->id);return $why?get_string($why,'mod_lumen10prova'):false;}
 public function prevent_new_attempt($numprevattempts,$lastattempt){return $this->prevent_access();}
 public function is_preflight_check_required($attemptid){global $USER,$SESSION;if($this->manager()||$attemptid)return false;$p=$this->prova;return !\mod_lumen10prova\engine::accepted($p,$USER->id)||($p->codeenabled&&(!isset($SESSION->l10prova_code[$p->id])||!hash_equals($p->codehash,$SESSION->l10prova_code[$p->id])));}
 public function add_preflight_check_form_fields(\mod_quiz\form\preflight_check_form $quizform,\MoodleQuickForm $mform,$attemptid){$p=$this->prova;
  $mform->addElement('static','l10warning','',get_string('confirmation','mod_lumen10prova'));
  if($text=\mod_lumen10prova\engine::policy_text($p)){$mform->addElement('static','l10rules','',nl2br(s($text)));$mform->addElement('advcheckbox','l10accept',get_string('acceptregulations','mod_lumen10prova'));}
  if($p->codeenabled){$mform->addElement('passwordunmask','l10code',get_string('accesscode','mod_lumen10prova'));$mform->setType('l10code',PARAM_RAW_TRIMMED);}
 }
 public function validate_preflight_check($data,$files,$errors,$attemptid){global $USER,$DB;$p=$this->prova;
  if(\mod_lumen10prova\engine::policy_text($p)&&empty($data['l10accept']))$errors['l10accept']=get_string('notconfirmed','mod_lumen10prova');
  if($p->codeenabled){$g=\mod_lumen10prova\engine::gate($p,$USER->id);if($g->codelocked>time()){$errors['l10code']=get_string('codelocked','mod_lumen10prova');}
   else if(!$p->codehash||!password_verify((string)($data['l10code']??''),$p->codehash)){$g->codefails++;if($g->codefails>=5){$g->codelocked=time()+60;$g->codefails=0;}$DB->update_record('lumen10prova_gate',$g);$errors['l10code']=get_string('wrongcode','mod_lumen10prova');}
  }return $errors;
 }
 public function notify_preflight_check_passed($attemptid){global $USER,$SESSION,$DB;$p=$this->prova;if(\mod_lumen10prova\engine::policy_text($p))\mod_lumen10prova\engine::record_acceptance($p,$USER->id);$SESSION->l10prova_code[$p->id]=$p->codehash;$g=\mod_lumen10prova\engine::gate($p,$USER->id);$DB->set_field('lumen10prova_gate','codefails',0,['id'=>$g->id]);}
 public function current_attempt_finished(){global $SESSION;unset($SESSION->l10prova_code[$this->prova->id]);}
 public function end_time($attempt){if(!empty($attempt->preview))return false;$deadline=\mod_lumen10prova\engine::deadline($this->prova,$attempt,$this->quiz);return $deadline?:false;}
 public function setup_attempt_page($page){global $USER;$p=$this->prova;$url=$page->url->get_path();
  if(!$this->manager()&&!\mod_lumen10prova\engine::participant($p,$USER->id))throw new \moodle_exception('notavailable','mod_lumen10prova');
  if(str_ends_with($url,'/attempt.php')||(!empty($p->exitlock)&&str_ends_with($url,'/summary.php'))){global $DB;$id=(int)$page->url->get_param('attempt');$a=$DB->get_record('quiz_attempts',['id'=>$id,'quiz'=>$p->quizid,'userid'=>$USER->id]);if(!$a||$a->preview)return;
   $generation=\mod_lumen10prova\attempt_security::initialise($p,$a);
   $page->requires->js_call_amd('mod_lumen10prova/room','attempt',[(int)$p->id,$id,['exitlock'=>!empty($p->exitlock),'generation'=>$generation,'monitor'=>(bool)$p->monitorenabled,'logevents'=>(bool)$p->logevents,'fullscreen'=>(bool)$p->fullscreen,'blockcopy'=>(bool)$p->blockcopy,'alerts'=>array_map('intval',explode(',',$p->alerts))]]);}
 }
}
