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
 * Maintenance.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova\task;
defined('MOODLE_INTERNAL') || die();
final class maintenance extends \core\task\scheduled_task {
 public function get_name(){return get_string('maintenance','mod_lumen10prova');}
 public function execute(){global $DB;$failed=0;$rs=$DB->get_recordset('lumen10prova');foreach($rs as $p){try{\mod_lumen10prova\engine::process($p);}catch(\Throwable $e){$failed++;mtrace('Lumen10 Prova '.$p->id.': '.get_class($e));}}$rs->close();
  $days=max(1,min(3650,(int)(get_config('mod_lumen10prova','retentiondays')?:90)));$cutoff=time()-$days*DAYSECS;
  $DB->delete_records_select('lumen10prova_log','timecreated<? AND kind IN (?,?,?,?,?,?,?)',[$cutoff,'focus_lost','focus_return','fullscreen_left','ip_changed','device_changed','reconnected','clipboard_blocked']);
  $DB->set_field_select('lumen10prova_live','iphash','','lastseen<?',[$cutoff]);$DB->set_field_select('lumen10prova_live','devicehash','','lastseen<?',[$cutoff]);
  if($failed)throw new \moodle_exception('maintenancefailed','mod_lumen10prova','',$failed);
 }
}
