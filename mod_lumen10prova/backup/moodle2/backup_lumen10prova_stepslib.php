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
 * Backup lumen10prova stepslib.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
class backup_lumen10prova_activity_structure_step extends backup_activity_structure_step {
 protected function define_structure(){global $DB;$fields=array_keys($DB->get_columns('lumen10prova'));$fields=array_values(array_diff($fields,['id','codehash']));
  $p=new backup_nested_element('lumen10prova',['id'],$fields);$p->set_source_table('lumen10prova',['id'=>backup::VAR_ACTIVITYID]);$p->annotate_files('mod_lumen10prova','intro',null);foreach(['examlogo','examwatermark']as$area)$p->annotate_files('mod_lumen10prova',$area,null);return $this->prepare_activity_structure($p);
 }
}
