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
 * Observer.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
final class observer {
 public static function maximum_grade(\core\event\base $event): void {
  global $DB;$data=$event->get_data();$p=engine::lookup((int)$data['objectid']);if(!$p)return;
  $DB->set_field('lumen10prova','grade',(float)$data['other']['newgrade'],['id'=>$p->id]);
  engine::audit($p,0,0,'maximum_grade_updated');
 }
 public static function visibility(\core\event\base $event): void {
  global $DB;$data=$event->get_data();$cm=$DB->get_record('course_modules',['id'=>$data['objectid']]);
  if(!$cm)return;
  $p=null;
  if($cm->module==$DB->get_field('modules','id',['name'=>'lumen10prova']))$p=$DB->get_record('lumen10prova',['id'=>$cm->instance]);
  else if($cm->module==$DB->get_field('modules','id',['name'=>'quiz']))$p=engine::lookup((int)$cm->instance);
  if($p)engine::sync_visibility($p);
 }
 public static function quiz(\core\event\base $event): void {
  global $DB;$data=$event->get_data();$quizid=(int)($data['other']['quizid']??0);$attemptid=(int)($data['other']['attemptid']??0);
  if(!$attemptid&&$data['objecttable']==='quiz_attempts')$attemptid=(int)$data['objectid'];
  $a=$attemptid?$DB->get_record('quiz_attempts',['id'=>$attemptid]):null;if($a)$quizid=$a->quiz;
  if(!$quizid&&$data['contextlevel']===CONTEXT_MODULE)$quizid=(int)$DB->get_field('course_modules','instance',['id'=>$data['contextinstanceid'],'module'=>$DB->get_field('modules','id',['name'=>'quiz'])]);
  $p=$quizid?engine::lookup($quizid):null;if(!$p||($a&&$a->preview))return;
  $uid=$a?$a->userid:(int)($data['relateduserid']??0);$kind=substr($event->eventname,strrpos($event->eventname,'\\')+1);
  if($a&&$p->monitorenabled){$lock=\core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('live-'.$attemptid,5);if($lock){try{engine::live($p,$a);}finally{$lock->release();}}}
  engine::audit($p,$uid,$attemptid,$kind);engine::review_policy($p);if($uid)engine::complete_user($p,$uid);
 }
}
