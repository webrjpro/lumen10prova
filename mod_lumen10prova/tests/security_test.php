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
 * Security test.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

final class security_test extends \advanced_testcase {
    private function fixture(bool $protected = true): array {
        global $CFG, $DB;
        $this->resetAfterTest(); $this->setAdminUser();
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $g = $this->getDataGenerator(); $c = $g->create_course();
        $s = $g->create_user(); $t = $g->create_user();
        $g->enrol_user($s->id, $c->id, 'student'); $g->enrol_user($t->id, $c->id, 'editingteacher');
        $p = $g->create_module('lumen10prova', ['course' => $c->id, 'exitlock' => (int)$protected, 'timelimit' => 3600]);
        $p = $DB->get_record('lumen10prova', ['id' => $p->id], '*', MUST_EXIST);
        $bank = $g->create_module('qbank', ['course' => $c->id]);
        $qg = $g->get_plugin_generator('core_question');
        $cat = $qg->create_question_category(['contextid' => \context_module::instance($bank->cmid)->id]);
        $q = $qg->create_question('truefalse', null, ['category' => $cat->id]);
        quiz_add_quiz_question($q->id, $DB->get_record('quiz', ['id' => $p->quizid]));
        \mod_quiz\quiz_settings::create($p->quizid)->get_grade_calculator()->recompute_quiz_sumgrades();
        $this->setUser($s); engine::record_acceptance($p, $s->id);
        $a = $g->get_plugin_generator('mod_quiz')->create_attempt($p->quizid, $s->id);
        return [$p, $a, $s, $t];
    }

    public function test_focus_exit_is_persistent_and_return_does_not_release(): void {
        global $DB;
        [$p, $a, $s] = $this->fixture(); $row = engine::live($p, $a);
        attempt_security::heartbeat($p, $a, $row, 'focus_lost', 1, time());
        $this->assertTrue(attempt_security::blocked($p, $a));
        attempt_security::heartbeat($p, $a, $row, 'focus_return', 1, time());
        $this->assertSame('securityblocked', engine::access_reason($p, $s->id));
        $this->assertNotEmpty(\mod_quiz\quiz_settings::create($p->quizid)->get_access_manager(time())->prevent_access());
        $this->assertSame('inprogress', $DB->get_field('quiz_attempts', 'state', ['id' => $a->id]));
        $this->assertSame(1, $DB->count_records('lumen10prova_log', ['attemptid' => $a->id, 'kind' => 'attempt_blocked']));
    }

    public function test_student_cannot_resume_and_teacher_requires_reason(): void {
        [$p, $a] = $this->fixture();
        $this->expectException(\required_capability_exception::class);
        attempt_security::resume($p, $a->id, 'Tentativa do aluno');
    }

    public function test_teacher_resume_changes_generation_without_changing_deadline(): void {
        global $DB;
        [$p, $a, $s, $t] = $this->fixture(); $row = engine::live($p, $a);
        $before = $DB->get_record('quiz_attempts', ['id' => $a->id]);
        attempt_security::heartbeat($p, $a, $row, 'modal_closed', 1, time());
        $this->setUser($t);
        try { attempt_security::resume($p, $a->id, ''); $this->fail('Missing reason accepted'); }
        catch (\moodle_exception $e) { $this->assertSame('securityreasonrequired', $e->errorcode); }
        attempt_security::resume($p, $a->id, 'Conferência com o aluno');
        $after = $DB->get_record('quiz_attempts', ['id' => $a->id]);
        $this->assertSame($before->timestart, $after->timestart);
        $this->assertSame($before->state, $after->state);
        $this->assertSame($before->sumgrades, $after->sumgrades);
        $this->assertFalse(attempt_security::blocked($p, $after));
        $row = engine::live($p, $after); $this->assertSame(2, (int)$row->securitygeneration);
        $this->setUser($s);
        try { attempt_security::heartbeat($p, $after, $row, 'page_left', 1, time()); $this->fail('Stale page accepted'); }
        catch (\moodle_exception $e) { $this->assertSame('securitystale', $e->errorcode); }
        $this->assertFalse(attempt_security::blocked($p, $after));
    }

    public function test_silence_blocks_even_if_browser_never_reports_exit(): void {
        global $DB;
        [$p, $a] = $this->fixture(); engine::live($p, $a);
        $DB->set_field('lumen10prova_live', 'lastseen', time() - 91, ['attemptid' => $a->id]);
        $this->assertTrue(attempt_security::blocked($p, $a));
        $_POST['l10securitygeneration'] = 1;
        try { attempt_security::guard_write($p, $a); $this->fail('Blocked response accepted'); }
        catch (\moodle_exception $e) { $this->assertSame('securityblocked', $e->errorcode); }
        finally { unset($_POST['l10securitygeneration']); }
    }

    public function test_unprotected_attempt_and_preview_do_not_get_exit_hold(): void {
        [$p, $a] = $this->fixture(false); $row = engine::live($p, $a);
        attempt_security::heartbeat($p, $a, $row, 'page_left', 0, time());
        $this->assertFalse(attempt_security::blocked($p, $a));
        $p->exitlock = 1; $a->preview = 1;
        attempt_security::heartbeat($p, $a, $row, 'print_attempt', 0, time());
        $this->assertFalse(attempt_security::blocked($p, $a));
    }

    public function test_print_attempt_holds_and_no_generation_cannot_write(): void {
        [$p, $a] = $this->fixture(); $row = engine::live($p, $a);
        try { attempt_security::guard_write($p, $a); $this->fail('Uninitialised writer accepted'); }
        catch (\moodle_exception $e) { $this->assertSame('securitystale', $e->errorcode); }
        attempt_security::heartbeat($p, $a, $row, 'print_attempt', 1, time());
        $this->assertTrue(attempt_security::blocked($p, $a));
    }

    public function test_expired_held_attempt_finishes_using_native_saved_answers(): void {
        global $DB;
        [$p, $a, $s, $t] = $this->fixture();
        $this->getDataGenerator()->get_plugin_generator('mod_quiz')->submit_responses($a->id, [1 => get_string('true', 'qtype_truefalse')], false, false);
        $row = engine::live($p, $a); attempt_security::heartbeat($p, $a, $row, 'page_left', 1, time());
        $DB->set_field('quiz_attempts', 'timestart', time() - 4000, ['id' => $a->id]);
        $this->setUser($t);
        try { attempt_security::resume($p, $a->id, 'Prazo encerrado'); $this->fail('Expired attempt released'); }
        catch (\moodle_exception $e) { $this->assertSame('attemptalreadyclosed', $e->errorcode); }
        engine::process($p);
        $saved = $DB->get_record('quiz_attempts', ['id' => $a->id]);
        $this->assertSame('finished', $saved->state);
        $this->assertEqualsWithDelta(1, (float)$saved->sumgrades, 0.001);
    }

    public function test_photo_reference_is_signed_and_lookup_is_restricted_to_staff(): void {
        [$p, $a, $s, $t] = $this->fixture();
        $obj = \mod_quiz\quiz_attempt::create($a->id);
        $marks = source_marks::page($obj, 0);
        $this->assertCount(1, $marks);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $marks[0]['image']);
        $token = $marks[0]['token'];
        try { source_marks::verify($p, $token); $this->fail('Student read private attribution'); }
        catch (\required_capability_exception $e) { $this->assertSame('nopermissions', $e->errorcode); }
        $this->setUser($t); $ref = source_marks::verify($p, $token);
        $this->assertSame((int)$a->id, $ref['attempt']);
        $this->assertSame((int)$s->id, $ref['userid']);
        $tampered = substr($token, 0, -1) . (str_ends_with($token, 'a') ? 'b' : 'a');
        try { source_marks::verify($p, $tampered); $this->fail('Changed signature accepted'); }
        catch (\moodle_exception $e) { $this->assertSame('invalidrequest', $e->errorcode); }
    }
}
