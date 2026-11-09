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
 * Backup lumen10prova activity task.class.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/lumen10prova/backup/moodle2/backup_lumen10prova_stepslib.php');
class backup_lumen10prova_activity_task extends backup_activity_task {
 protected function define_my_settings(){}
 protected function define_my_steps(){$this->add_step(new backup_lumen10prova_activity_structure_step('lumen10prova_structure','lumen10prova.xml'));}
 public static function encode_content_links($content){global $CFG;$base=preg_quote($CFG->wwwroot,'/');return preg_replace('/('.$base.'\/mod\/lumen10prova\/view.php\?id=)([0-9]+)/','$@LUMEN10PROVAVIEWBYID*$2@$',$content);}
}
