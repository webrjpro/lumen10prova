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
 * Certificado.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');require_login();require_capability('moodle/site:config',context_system::instance());
$PAGE->set_url('/mod/lumen10prova/certificado.php');$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('integritytitle','mod_lumen10prova'));$PAGE->set_heading(get_string('pluginname','mod_lumen10prova'));
$checks=[];foreach(['mod_lumen10prova','quizaccess_lumen10prova']as$component) {
 $class='\\'.$component.'\\integrity';$r=$class::verify();$ok=$r['signature']&&$r['files'];
 $checks[]=['component'=>$component,'ok'=>$ok,'message'=>get_string($ok?'integrityok':'integrityfailed','mod_lumen10prova'),'differences'=>$r['differences']];
}
echo $OUTPUT->header();echo $OUTPUT->render_from_template('mod_lumen10prova/integrity',['checks'=>$checks]);echo $OUTPUT->footer();
