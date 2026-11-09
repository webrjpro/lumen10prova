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
 * Sync owned quizzes.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova\task;

defined('MOODLE_INTERNAL') || die();

/** Repair existing ownership after upgrade, when course and grade APIs are available. */
final class sync_owned_quizzes extends \core\task\adhoc_task {
 public function execute(): void {
  global $DB;
  foreach($DB->get_records('lumen10prova') as $prova){
   \mod_lumen10prova\engine::sync_visibility($prova);
  }
 }
}
