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
 * Action recorded.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova\event;
defined('MOODLE_INTERNAL') || die();
final class action_recorded extends \core\event\base {
 protected function init():void {$this->data['objecttable']='lumen10prova_log';$this->data['crud']='c';$this->data['edulevel']=self::LEVEL_TEACHING;}
 public static function get_name():string {return get_string('eventaction','mod_lumen10prova');}
 public function get_description():string {return 'Institutional exam action '.($this->other['kind']??'').', actor '.$this->userid.'.';}
 public function get_url():\moodle_url {return new \moodle_url('/mod/lumen10prova/reports.php',['id'=>$this->contextinstanceid]);}
 public static function get_objectid_mapping(){return \core\event\base::NOT_MAPPED;}
 public static function get_other_mapping(){return false;}
}
