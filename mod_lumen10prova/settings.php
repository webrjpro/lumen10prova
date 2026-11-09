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
 * Settings.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
if($ADMIN->fulltree){
 $settings->add(new admin_setting_configcheckbox('mod_lumen10prova/enableai',get_string('aienabled','mod_lumen10prova'),get_string('aiconfig','mod_lumen10prova'),0));
 $settings->add(new admin_setting_configtext('mod_lumen10prova/retentiondays',get_string('retentiondays','mod_lumen10prova'),get_string('retentiondaysdesc','mod_lumen10prova'),90,PARAM_INT));
 $settings->add(new admin_setting_heading('mod_lumen10prova/nativeai',get_string('providers','mod_lumen10prova'),html_writer::link(new moodle_url('/admin/settings.php',['section'=>'aiprovider']),get_string('providers','mod_lumen10prova'))));
 $settings->add(new admin_setting_heading('mod_lumen10prova/certificate',get_string('integrity','mod_lumen10prova'),html_writer::link(new moodle_url('/mod/lumen10prova/certificado.php'),get_string('integrity','mod_lumen10prova'))));
}
