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
 * Room.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');require_once($CFG->dirroot.'/mod/quiz/locallib.php');
$id=required_param('id',PARAM_INT);[$p,$cm,$course,$context]=\mod_lumen10prova\ui::load($id);
require_capability('mod/lumen10prova:monitor',$context);
\mod_lumen10prova\ui::header($p,$cm,$course,'room');
\mod_lumen10prova\ui::render('room',['id'=>$id,'sesskey'=>sesskey(),
 'actionurl'=>(new moodle_url('/mod/lumen10prova/action.php'))->out(false),
 'canmanage'=>has_capability('mod/lumen10prova:manage',$context),'codeenabled'=>(bool)$p->codeenabled]);
$PAGE->requires->js_call_amd('mod_lumen10prova/room','monitor',[$id]);\mod_lumen10prova\ui::footer();
