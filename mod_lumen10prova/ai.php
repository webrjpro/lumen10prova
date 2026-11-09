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
 * Ai.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
$id=required_param('id',PARAM_INT);
[$p,$cm,$course,$context]=\mod_lumen10prova\ui::load($id);require_capability('mod/lumen10prova:ai',$context);
$enabled=$p->aienabled&&get_config('mod_lumen10prova','enableai');$answer='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 require_sesskey();
 if(!$enabled||!class_exists('\\core_ai\\manager'))throw new moodle_exception('aiunavailable','mod_lumen10prova');
 $manager=new \core_ai\manager();$action=\core_ai\aiactions\generate_text::class;
 if(!$manager->is_action_enabled('aiplacement_editor',$action)||!$manager->is_action_enabled_in_context($context,$action))throw new moodle_exception('aiunavailable','mod_lumen10prova');
 if(!optional_param('consent',0,PARAM_BOOL))throw new moodle_exception('notconfirmed','mod_lumen10prova');
 if(!\core_ai\manager::get_user_policy_status($USER->id))throw new moodle_exception('aiunavailable','mod_lumen10prova');
 $text=required_param('prompt',PARAM_TEXT);if(core_text::strlen($text)>12000)throw new moodle_exception('invalidsettings','mod_lumen10prova');
 $prompt=get_string('aisystemprompt','mod_lumen10prova').$text;
 $response=$manager->process_action(new \core_ai\aiactions\generate_text($context->id,$USER->id,$prompt));
 if(!$response->get_success())throw new moodle_exception('aiunavailable','mod_lumen10prova');
 $answer=core_text::substr($response->get_response_data()['generatedcontent']??'',0,30000);
 \mod_lumen10prova\engine::audit($p,0,0,'ai_requested');
}
\mod_lumen10prova\ui::header($p,$cm,$course,'ai');
\mod_lumen10prova\ui::render('ai',['id'=>$id,'sesskey'=>sesskey(),'enabled'=>(bool)$enabled,'answer'=>$answer]);
\mod_lumen10prova\ui::footer();
