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
 * Ui.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();
final class ui {
 public static function load(int $cmid): array {
  global $DB;
  $cm = get_coursemodule_from_id('lumen10prova', $cmid, 0, false, MUST_EXIST);
  $course = get_course($cm->course);
  require_login($course, false, $cm);
  $context = \context_module::instance($cm->id);
  require_capability('mod/lumen10prova:view', $context);
  return [$DB->get_record('lumen10prova', ['id'=>$cm->instance], '*', MUST_EXIST), $cm, $course, $context];
 }
 public static function header(\stdClass $p, \stdClass $cm, \stdClass $course, string $page): void {
  global $PAGE, $OUTPUT;
  $PAGE->set_url('/mod/lumen10prova/'.$page.'.php', ['id'=>$cm->id]);
  $PAGE->set_cm($cm, $course); $PAGE->set_pagelayout('incourse');
  $PAGE->set_title(format_string($p->name)); $PAGE->set_heading($course->fullname);
  echo $OUTPUT->header(); echo \html_writer::start_div('l10-exam');
 }
 public static function footer(): void { global $OUTPUT; echo \html_writer::end_div().$OUTPUT->footer(); }
 public static function render(string $template, array $data): void {
  global $OUTPUT; echo $OUTPUT->render_from_template('mod_lumen10prova/'.$template, $data);
 }
 public static function action(string $path, array $params, string $label): array {
  return ['url'=>(new \moodle_url($path, $params))->out(false), 'label'=>$label];
 }
 public static function link(string $path, array $params, string $label): string {
  global $OUTPUT; return $OUTPUT->render_from_template('mod_lumen10prova/link', self::action($path,$params,$label));
 }
 public static function post(int $cmid, string $action, string $label, array $extra=[]): string {
  global $OUTPUT;
  $fields=array_merge(['id'=>$cmid,'sesskey'=>sesskey(),'action'=>$action],$extra);
  return $OUTPUT->render_from_template('mod_lumen10prova/post', [
   'url'=>(new \moodle_url('/mod/lumen10prova/action.php'))->out(false),'label'=>$label,
   'fields'=>array_map(fn($k,$v)=>['name'=>$k,'value'=>$v], array_keys($fields), array_values($fields)),
  ]);
 }
}
