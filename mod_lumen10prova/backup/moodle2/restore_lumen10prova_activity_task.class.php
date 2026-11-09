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
 * Restore lumen10prova activity task.class.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/lumen10prova/backup/moodle2/restore_lumen10prova_stepslib.php');
class restore_lumen10prova_activity_task extends restore_activity_task {
 protected function define_my_settings(){}
 protected function define_my_steps(){$this->add_step(new restore_lumen10prova_activity_structure_step('lumen10prova_structure','lumen10prova.xml'));}
 public static function define_decode_contents(){return [new restore_decode_content('lumen10prova',['intro'],'lumen10prova')];}
 public static function define_decode_rules(){return [new restore_decode_rule('LUMEN10PROVAVIEWBYID','/mod/lumen10prova/view.php?id=$1','course_module')];}
 public static function define_restore_log_rules(){return [];}
 public function after_restore(){global $DB;$p=$DB->get_record('lumen10prova',['id'=>$this->get_activityid()],'*',MUST_EXIST);$mapping=restore_dbops::get_backup_ids_record($this->get_restoreid(),'quiz',$p->quizid);$quizid=$mapping?(int)$mapping->newitemid:0;
  if($quizid&&$DB->record_exists('quiz',['id'=>$quizid,'course'=>$p->course])&&!$DB->record_exists('lumen10prova',['quizid'=>$quizid])){$p->quizid=$quizid;$DB->update_record('lumen10prova',$p);}
  else{$p->coursemodule=$this->get_moduleid();\mod_lumen10prova\engine::create_quiz($p);}
  \mod_lumen10prova\engine::sync_visibility($p);\mod_lumen10prova\engine::review_policy($p);
 }
}
