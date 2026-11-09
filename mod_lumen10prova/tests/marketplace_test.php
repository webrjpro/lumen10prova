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
 * Marketplace test.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

use mod_lumen10prova\external\api;

/** Regression coverage for external services, imports, viewing and monitor data. */
final class marketplace_test extends \advanced_testcase {
    private function fixture(array $settings = []): array {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/lumen10prova/lib.php');
        $g = $this->getDataGenerator();
        $course = $g->create_course(['enablecompletion' => 1]);
        $student = $g->create_user();
        $teacher = $g->create_user();
        $g->enrol_user($student->id, $course->id, 'student');
        $g->enrol_user($teacher->id, $course->id, 'editingteacher');
        $activity = $g->create_module('lumen10prova', $settings + ['course' => $course->id,
            'timelimit' => 3600, 'exitlock' => 0, 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1]);
        $p = $DB->get_record('lumen10prova', ['id' => $activity->id], '*', MUST_EXIST);
        $bank = $g->create_module('qbank', ['course' => $course->id]);
        $qg = $g->get_plugin_generator('core_question');
        $category = $qg->create_question_category(['contextid' => \context_module::instance($bank->cmid)->id]);
        $question = $qg->create_question('truefalse', null, ['category' => $category->id]);
        quiz_add_quiz_question($question->id, $DB->get_record('quiz', ['id' => $p->quizid]));
        \mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->recompute_quiz_sumgrades();
        return [$p, $course, $student, $teacher, $activity];
    }

    public function test_view_records_event_and_view_completion(): void {
        [$p, $course, $student, , $activity] = $this->fixture();
        $this->setUser($student);
        $cm = get_coursemodule_from_id('lumen10prova', $activity->cmid);
        $sink = $this->redirectEvents();
        lumen10prova_view($p, $course, $cm, \context_module::instance($cm->id));
        $events = array_filter($sink->get_events(), fn($event) => $event instanceof event\course_module_viewed);
        $this->assertCount(1, $events);
        $this->assertEquals($p->id, reset($events)->objectid);
        $completion = (new \completion_info($course))->get_data($cm, false, $student->id);
        $this->assertEquals(1, $completion->viewed);
        $this->assertTrue(lumen10prova_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
    }

    public function test_preview_is_scoped_to_user_exam_and_expiry(): void {
        global $USER;
        [$p, , $student, $teacher] = $this->fixture();
        $this->setUser($teacher);
        $rows = [['enunciado' => 'Teacher-authored content']];
        $token = import_preview::store((int)$p->id, $rows);
        $this->assertSame($rows, import_preview::get((int)$p->id, $token)['rows']);
        $this->assertNull(import_preview::get((int)$p->id + 1, $token));
        $this->setUser($student);
        $this->assertNull(import_preview::get((int)$p->id, $token));
        $this->setUser($teacher);
        $cache = \cache::make('mod_lumen10prova', 'importpreview', ['userid' => (int)$USER->id]);
        $pending = $cache->get($token); $pending['expires'] = time() - 1; $cache->set($token, $pending);
        $this->assertNull(import_preview::get((int)$p->id, $token));
        import_preview::delete($token);
        $this->assertFalse($cache->get($token));
    }

    public function test_heartbeat_preserves_owner_generation_and_schema(): void {
        [$p, , $student] = $this->fixture(['exitlock' => 1]);
        $this->setUser($student);
        $a = $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_attempt($p->quizid, $student->id);
        $generation = attempt_security::initialise($p, $a);
        $result = api::heartbeat((int)$p->id, (int)$a->id, '', $generation);
        $clean = \core_external\external_api::clean_returnvalue(api::heartbeat_returns(), $result);
        $this->assertSame('inprogress', $clean['state']);
        $this->assertFalse($clean['blocked']);
        $held = api::heartbeat((int)$p->id, (int)$a->id, 'focus_lost', $generation);
        $this->assertTrue($held['blocked']);
        $this->expectException(\moodle_exception::class);
        api::heartbeat((int)$p->id, (int)$a->id, '', $generation + 1);
    }

    public function test_response_service_rejects_another_users_attempt(): void {
        [$p, $course, $student] = $this->fixture();
        $this->setUser($student);
        $a = $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_attempt($p->quizid, $student->id);
        $own = api::answersheet((int)$p->id, (int)$a->id);
        $this->assertEquals(1, $own['total']);
        \core_external\external_api::clean_returnvalue(api::answersheet_returns(), $own);
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($other->id, $course->id, 'student');
        $this->setUser($other);
        $this->expectException(\dml_missing_record_exception::class);
        api::answersheet((int)$p->id, (int)$a->id);
    }

    public function test_monitor_requires_monitor_capability(): void {
        [$p, , $student, , $activity] = $this->fixture();
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        api::poll((int)$activity->cmid);
    }

    public function test_bulk_monitor_respects_native_user_group_overrides_and_suspension(): void {
        global $DB;
        [$p, $course, $student, $teacher, $activity] = $this->fixture();
        $this->setUser($student);
        $a = $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_attempt($p->quizid, $student->id);
        engine::live($p, $a);
        $this->setAdminUser();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $DB->insert_record('quiz_overrides', ['quiz' => $p->quizid, 'groupid' => $group->id, 'timelimit' => 7200]);
        $DB->insert_record('quiz_overrides', ['quiz' => $p->quizid, 'userid' => $student->id, 'timelimit' => 1800]);
        $native = \mod_quiz\quiz_settings::create($p->quizid, $student->id)->get_quiz();
        $expected = engine::deadline($p, $a, $native) - time();
        $this->setUser($teacher);
        $data = api::poll((int)$activity->cmid);
        \core_external\external_api::clean_returnvalue(api::poll_returns(), $data);
        $row = array_values(array_filter($data['rows'], fn($row) => $row['userid'] == $student->id))[0];
        $this->assertEqualsWithDelta($expected, $row['remaining'], 2);
        $DB->set_field('user', 'suspended', 1, ['id' => $student->id]);
        $this->assertEmpty(engine::status_rows($p));
    }

    public function test_metadata_describes_all_persisted_personal_fields(): void {
        $collection = privacy\provider::get_metadata(new \core_privacy\local\metadata\collection('mod_lumen10prova'));
        $tables = [];
        foreach ($collection->get_collection() as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $tables[$item->get_name()] = $item->get_privacy_fields();
                foreach ($item->get_privacy_fields() as $key) {
                    $this->assertStringStartsWith('privacy:metadata:', $key);
                    $this->assertTrue(get_string_manager()->string_exists($key, 'mod_lumen10prova'));
                }
            }
        }
        $this->assertArrayHasKey('iphash', $tables['lumen10prova_live']);
        $this->assertArrayHasKey('actorid', $tables['lumen10prova_log']);
        $this->assertArrayHasKey('acceptedhash', $tables['lumen10prova_gate']);
    }
}
