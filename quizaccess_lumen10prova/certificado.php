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
 * @package    quizaccess_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_login();require_capability('moodle/site:config',context_system::instance());
$PAGE->set_url('/mod/quiz/accessrule/lumen10prova/certificado.php');$PAGE->set_context(context_system::instance());$PAGE->set_title(get_string('integritytitle','quizaccess_lumen10prova'));$PAGE->set_heading(get_string('pluginname','quizaccess_lumen10prova'));
$r=\quizaccess_lumen10prova\integrity::verify();echo $OUTPUT->header();
$ok=$r['signature']&&$r['files'];
echo $OUTPUT->render_from_template('quizaccess_lumen10prova/integrity',[
 'ok'=>$ok,'message'=>get_string($ok?'integrityok':'integrityfailed','quizaccess_lumen10prova')]);
echo $OUTPUT->footer();
