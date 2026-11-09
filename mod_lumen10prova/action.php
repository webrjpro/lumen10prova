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
 * Action.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');require_once($CFG->dirroot.'/mod/quiz/locallib.php');
if($_SERVER['REQUEST_METHOD']!=='POST')throw new moodle_exception('invalidrequest');require_sesskey();
$id=required_param('id',PARAM_INT);[$p,$cm,$course,$context]=\mod_lumen10prova\ui::load($id);require_capability('mod/lumen10prova:manage',$context);
$action=required_param('action',PARAM_ALPHA);$message=get_string('changesrecorded','mod_lumen10prova');
switch($action){
 case 'resume':\mod_lumen10prova\attempt_security::resume($p,required_param('attempt',PARAM_INT),required_param('reason',PARAM_TEXT));$message=get_string('resumeauthorised','mod_lumen10prova');break;
 case 'releaseall':$users=get_enrolled_users(context_course::instance($course->id),'mod/quiz:attempt',$p->groupid,'u.id');$n=\mod_lumen10prova\engine::release($p,array_keys($users));$message=get_string('participantsreleased','mod_lumen10prova',$n);break;
 case 'release':$uid=required_param('userid',PARAM_INT);$n=\mod_lumen10prova\engine::release($p,[$uid]);$message=get_string('participantsreleased','mod_lumen10prova',$n);break;
 case 'rotatecode':$code=(string)random_int(100000,999999);$DB->set_field('lumen10prova','codehash',password_hash($code,PASSWORD_DEFAULT),['id'=>$p->id]);\mod_lumen10prova\engine::audit($p,0,0,'code_rotated');
  \mod_lumen10prova\ui::header($p,$cm,$course,'room');\mod_lumen10prova\ui::render('code',['title'=>get_string('newcode','mod_lumen10prova',$code),'url'=>(new moodle_url('/mod/lumen10prova/room.php',['id'=>$id]))->out(false)]);\mod_lumen10prova\ui::footer();exit;
 case 'publish':if(!$p->released){$DB->set_field('lumen10prova','released',1,['id'=>$p->id]);$p->released=1;\mod_lumen10prova\engine::audit($p,0,0,'results_released');}\mod_lumen10prova\engine::review_policy($p);break;
 default:throw new moodle_exception('invalidrequest');
}
redirect(new moodle_url('/mod/lumen10prova/room.php',['id'=>$id]),$message,null,\core\output\notification::NOTIFY_SUCCESS);
