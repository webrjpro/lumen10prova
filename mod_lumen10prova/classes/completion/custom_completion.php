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
 * Custom completion.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova\completion;
defined('MOODLE_INTERNAL') || die();
final class custom_completion extends \core_completion\activity_custom_completion {
 public function get_state(string $rule):int {global $DB;$this->validate_rule($rule);$p=$DB->get_record('lumen10prova',['id'=>$this->cm->instance],'*',MUST_EXIST);$d=clone $p;foreach(self::get_defined_custom_rules() as $r)$d->$r=$r===$rule?1:0;return \mod_lumen10prova\engine::completion($d,$this->userid)?COMPLETION_COMPLETE:COMPLETION_INCOMPLETE;}
 public static function get_defined_custom_rules():array{return ['completionattempt','completiongraded','completionpassed'];}
 public function get_custom_rule_descriptions():array{$out=[];foreach(self::get_defined_custom_rules() as $r)$out[$r]=get_string($r,'mod_lumen10prova');return $out;}
 public function get_sort_order():array{return self::get_defined_custom_rules();}
}
