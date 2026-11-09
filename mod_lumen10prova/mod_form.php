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
 * Mod form.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');
class mod_lumen10prova_mod_form extends moodleform_mod {
 public function definition() {
  global $DB;$m=$this->_form;$m->addElement('header','general',get_string('general','form'));
  $m->addElement('text','name',get_string('name'),'maxlength="255"');$m->setType('name',PARAM_TEXT);$m->addRule('name',null,'required');
  $this->standard_intro_elements();$m->addElement('header','examsettings',get_string('examsettings','mod_lumen10prova'));
  foreach(['timeopen'=>'quizopen','timeclose'=>'quizclose'] as $n=>$label)$m->addElement('date_time_selector',$n,get_string($label,'quiz'),['optional'=>true]);
  $m->addElement('duration','timelimit',get_string('timelimit','quiz'),['optional'=>false]);$m->setDefault('timelimit',3600);
  foreach(['attempts'=>1,'grade'=>10,'gradepass'=>6,'blocksize'=>10] as $n=>$d){$m->addElement('text',$n,get_string($n,'mod_lumen10prova'));$m->setType($n,in_array($n,['grade','gradepass'])?PARAM_FLOAT:PARAM_INT);$m->setDefault($n,$d);}
  $m->addElement('select','grademethod',get_string('grademethod','quiz'),[1=>get_string('gradehighest','quiz'),2=>get_string('gradeaverage','quiz'),3=>get_string('attemptfirst','quiz'),4=>get_string('attemptlast','quiz')]);
  $m->addElement('select','navigation',get_string('navigation','mod_lumen10prova'),['free'=>get_string('navigationfree','mod_lumen10prova'),'seq'=>get_string('navigationseq','mod_lumen10prova'),'blocks'=>get_string('navigationblocks','mod_lumen10prova')]);
  foreach(['shufflequestions'=>1,'shuffleanswers'=>1] as $n=>$d){$m->addElement('advcheckbox',$n,get_string($n,'mod_lumen10prova'));$m->setDefault($n,$d);}
  $m->addElement('header','accesssettings',get_string('accesssettings','mod_lumen10prova'));
  $m->addElement('passwordunmask','exampassword',get_string('requirepassword','quiz'));$m->setType('exampassword',PARAM_RAW);
  $m->addElement('text','subnet',get_string('requiresubnet','quiz'));$m->setType('subnet',PARAM_RAW_TRIMMED);
  $groups=[0=>get_string('allparticipants')];foreach(groups_get_all_groups($this->_course->id) as $g)$groups[$g->id]=format_string($g->name);
  $m->addElement('select','groupid',get_string('authorisedgroup','mod_lumen10prova'),$groups);
  foreach(['codeenabled','manualstart','syncstart','monitorenabled','logevents','fullscreen','blockcopy','anonymousgrading','aienabled'] as $n){$m->addElement('advcheckbox',$n,get_string($n,'mod_lumen10prova'));$m->setDefault($n,$n==='monitorenabled'?1:0);}
  $m->disabledIf('syncstart','manualstart','notchecked');$m->disabledIf('logevents','monitorenabled','notchecked');
  $m->addElement('advcheckbox','exitlock',get_string('exitlock','mod_lumen10prova'));$m->setDefault('exitlock',1);$m->addHelpButton('exitlock','exitlock','mod_lumen10prova');
  foreach(['codeenabled','manualstart','syncstart','monitorenabled','logevents','fullscreen','blockcopy','anonymousgrading','aienabled']as$n)$m->addHelpButton($n,$n,'mod_lumen10prova');
  $m->disabledIf('blocksize','navigation','neq','blocks');
  $m->addElement('text','profilefields',get_string('profilefields','mod_lumen10prova'));$m->setType('profilefields',PARAM_RAW_TRIMMED);
  $m->addElement('textarea','regulations',get_string('regulations','mod_lumen10prova'),['rows'=>5]);$m->setType('regulations',PARAM_TEXT);
  $m->addElement('text','alerts',get_string('alerts','mod_lumen10prova'));$m->setType('alerts',PARAM_RAW_TRIMMED);$m->setDefault('alerts','900,300,60');
  $m->addElement('header','resultssettings',get_string('resultssettings','mod_lumen10prova'));
  $m->addElement('select','releasepolicy',get_string('releasepolicy','mod_lumen10prova'),['immediate'=>get_string('releaseimmediate','mod_lumen10prova'),'close'=>get_string('releaseclose','mod_lumen10prova'),'graded'=>get_string('releasegraded','mod_lumen10prova'),'date'=>get_string('releasedate','mod_lumen10prova'),'manual'=>get_string('releasemanual','mod_lumen10prova')]);
  $m->setDefault('releasepolicy','close');$m->addElement('date_time_selector','releasetime',get_string('releasetime','mod_lumen10prova'),['optional'=>true]);
  foreach(['showmarks'=>1,'showfeedback'=>1,'showanswers'=>0] as $n=>$d){$m->addElement('advcheckbox',$n,get_string($n,'mod_lumen10prova'));$m->setDefault($n,$d);}
  $m->addElement('textarea','rubric',get_string('rubric','mod_lumen10prova'),['rows'=>5]);$m->setType('rubric',PARAM_TEXT);
  $m->addElement('header','appearancesettings',get_string('appearancesettings','mod_lumen10prova'));
  foreach(['modalenabled'=>get_string('modalenabled','mod_lumen10prova'),'paperstyle'=>get_string('paperstyle','mod_lumen10prova'),'answersheetenabled'=>get_string('answersheetenabled','mod_lumen10prova')]as$n=>$label){$m->addElement('advcheckbox',$n,$label);$m->setDefault($n,1);}
  foreach(['examlogo'=>get_string('examlogo','mod_lumen10prova'),'examwatermark'=>get_string('examwatermark','mod_lumen10prova')]as$n=>$label)$m->addElement('filemanager',$n,$label,null,\mod_lumen10prova\appearance::options());
  $m->addElement('text','watermarkopacity',get_string('watermarkopacity','mod_lumen10prova'));$m->setType('watermarkopacity',PARAM_FLOAT);$m->setDefault('watermarkopacity',0.06);
  $this->standard_coursemodule_elements();$this->add_action_buttons();
 }
 public function data_preprocessing(&$defaults) {
  foreach(['grade','gradepass']as$n)if(isset($defaults[$n]))$defaults[$n]=number_format((float)$defaults[$n],2,'.','');
  global $DB;foreach(['examlogo','examwatermark']as$area){$draft=file_get_submitted_draft_itemid($area);$context=$this->context;file_prepare_draft_area($draft,$context->id,'mod_lumen10prova',$area,0,\mod_lumen10prova\appearance::options());$defaults[$area]=$draft;}if(!empty($this->current->quizid)){$q=$DB->get_record('quiz',['id'=>$this->current->quizid]);$defaults['exampassword']=$q->password;$defaults['subnet']=$q->subnet;}
 }
 public function validation($data,$files) {
  $errors=parent::validation($data,$files);
  foreach(\mod_lumen10prova\engine::validation($data,$this->_course->id) as $key=>$message)$errors[$key]=$message;
  if(!empty($this->current->id)&&!empty($data['exitlock'])!==!empty($this->current->exitlock)&&$GLOBALS['DB']->record_exists_select('quiz_attempts','quiz=? AND preview=0 AND state IN (?,?)',[$this->current->quizid,'inprogress','overdue']))$errors['exitlock']=get_string('securitypolicylocked','mod_lumen10prova');
  if(!empty($this->current->id)&&\mod_lumen10prova\engine::has_attempts($this->current)&&\mod_lumen10prova\engine::structural_change($this->current,(object)$data))$errors['timelimit']=get_string('lockedsettings','mod_lumen10prova');
  return $errors;
 }
 public function add_completion_rules() {
  $m=$this->_form;$names=['completionattempt','completiongraded','completionpassed'];foreach($names as $n)$m->addElement('advcheckbox',$n,get_string($n,'mod_lumen10prova'));return $names;
 }
 public function completion_rule_enabled($data) {return !empty($data['completionattempt'])||!empty($data['completiongraded'])||!empty($data['completionpassed']);}
}
