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
 * Api.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova\external;
defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_lumen10prova\engine;
use mod_lumen10prova\attempt_security;

/** Validated services for monitoring and the owner's response summary. */
class api extends external_api {
    private static function load(int $id, bool $cmid = false): array {
        global $DB;
        $cm = $cmid ? get_coursemodule_from_id('lumen10prova', $id, 0, false, MUST_EXIST) :
            get_coursemodule_from_instance('lumen10prova', $id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_login(get_course($cm->course), false, $cm);
        require_capability('mod/lumen10prova:view', $context);
        return [$DB->get_record('lumen10prova', ['id' => $cm->instance], '*', MUST_EXIST), $context];
    }

    public static function heartbeat_parameters(): external_function_parameters {
        return new external_function_parameters([
            'prova' => new external_value(PARAM_INT, 'Exam instance ID'),
            'attempt' => new external_value(PARAM_INT, 'Own native attempt ID'),
            'event' => new external_value(PARAM_ALPHANUMEXT, 'Optional browser event', VALUE_DEFAULT, ''),
            'generation' => new external_value(PARAM_INT, 'Security generation', VALUE_DEFAULT, 0),
        ]);
    }

    public static function heartbeat(int $prova, int $attempt, string $event = '', int $generation = 0): array {
        global $DB, $USER, $CFG;
        $args = self::validate_parameters(self::heartbeat_parameters(), compact('prova', 'attempt', 'event', 'generation'));
        [$p] = self::load($args['prova']);
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $a = $DB->get_record('quiz_attempts', ['id' => $args['attempt'], 'quiz' => $p->quizid,
            'userid' => $USER->id, 'preview' => 0], '*', MUST_EXIST);
        if (!$p->monitorenabled || !engine::participant($p, $USER->id)) {
            throw new \moodle_exception('notavailable', 'mod_lumen10prova');
        }
        $lock = \core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('live-' . $a->id, 5);
        if (!$lock) {
            throw new \moodle_exception('invalidrequest');
        }
        try {
            $row = engine::live($p, $a);
            $now = time();
            $gap = $now - $row->lastseen;
            attempt_security::heartbeat($p, $a, $row, $args['event'], $args['generation'], $now);
            if ($gap >= 5) {
                if ($p->logevents) {
                    $salt = $CFG->passwordsaltmain ?? $CFG->siteidentifier;
                    $ip = hash('sha256', getremoteaddr() . ':' . $salt . ':' . $p->id);
                    $device = hash('sha256', session_id() . ':' . $salt . ':' . $p->id);
                    foreach (['iphash' => ['ipchanges', 'ip_changed', $ip],
                            'devicehash' => ['devicechanges', 'device_changed', $device]] as $field => $spec) {
                        [$counter, $kind, $value] = $spec;
                        if ($row->$field && !hash_equals($row->$field, $value)) {
                            $row->$counter++;
                            engine::audit($p, $USER->id, $a->id, $kind);
                        }
                        $row->$field = $value;
                    }
                    if ($gap > 90) {
                        $row->reconnections++;
                        engine::audit($p, $USER->id, $a->id, 'reconnected');
                    }
                    if (in_array($args['event'], ['focus_lost', 'focus_return', 'fullscreen_left', 'clipboard_blocked'], true)) {
                        $row->focuschanges += $args['event'] === 'focus_lost' ? 1 : 0;
                        engine::audit($p, $USER->id, $a->id, $args['event']);
                    }
                }
                $row->lastseen = $now;
                $DB->update_record('lumen10prova_live', $row);
            }
            $obj = \mod_quiz\quiz_attempt::create($a->id);
            $deadline = engine::deadline($p, $a, $obj->get_quiz());
            if ($deadline && $now >= $deadline && in_array($a->state, ['inprogress', 'overdue'], true)) {
                $obj->handle_if_time_expired($now, false);
                $a = $DB->get_record('quiz_attempts', ['id' => $a->id], '*', MUST_EXIST);
            }
            return ['server' => $now, 'deadline' => $deadline, 'state' => $a->state,
                'blocked' => attempt_security::blocked($p, $a), 'generation' => (int)$row->securitygeneration];
        } finally {
            $lock->release();
        }
    }

    public static function heartbeat_returns(): external_single_structure {
        return new external_single_structure([
            'server' => new external_value(PARAM_INT, 'Server timestamp'),
            'deadline' => new external_value(PARAM_INT, 'Effective deadline'),
            'state' => new external_value(PARAM_ALPHANUMEXT, 'Native attempt state'),
            'blocked' => new external_value(PARAM_BOOL, 'Attempt held'),
            'generation' => new external_value(PARAM_INT, 'Current security generation'),
        ]);
    }

    public static function poll_parameters(): external_function_parameters {
        return new external_function_parameters(['id' => new external_value(PARAM_INT, 'Exam course-module ID')]);
    }

    public static function poll(int $id): array {
        $args = self::validate_parameters(self::poll_parameters(), ['id' => $id]);
        [$p, $context] = self::load($args['id'], true);
        require_capability('mod/lumen10prova:monitor', $context);
        $rows = engine::status_rows($p);
        $totals = [];
        foreach ($rows as $row) {
            $totals[$row['status']] = ($totals[$row['status']] ?? 0) + 1;
        }
        $counts = [];
        foreach ($totals as $status => $count) {
            $counts[] = ['status' => $status, 'count' => $count];
        }
        return ['rows' => $rows, 'counts' => $counts, 'server' => time(),
            'canrelease' => has_capability('mod/lumen10prova:manage', $context)];
    }

    public static function poll_returns(): external_single_structure {
        $fields = [];
        foreach (['userid', 'attempt', 'remaining', 'lastseen', 'focuschanges', 'reconnections', 'ipchanges', 'devicechanges'] as $field) {
            $fields[$field] = new external_value(PARAM_INT, $field);
        }
        $fields['name'] = new external_value(PARAM_TEXT, 'Participant name');
        $fields['status'] = new external_value(PARAM_ALPHANUMEXT, 'Attempt state');
        $fields['candidate'] = new external_value(PARAM_TEXT, 'Pseudonymous candidate ID');
        return new external_single_structure([
            'rows' => new external_multiple_structure(new external_single_structure($fields)),
            'counts' => new external_multiple_structure(new external_single_structure([
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Attempt state'),
                'count' => new external_value(PARAM_INT, 'Participant count'),
            ])),
            'server' => new external_value(PARAM_INT, 'Server timestamp'),
            'canrelease' => new external_value(PARAM_BOOL, 'May authorise participants'),
        ]);
    }

    public static function answersheet_parameters(): external_function_parameters {
        return new external_function_parameters([
            'prova' => new external_value(PARAM_INT, 'Exam instance ID'),
            'attempt' => new external_value(PARAM_INT, 'Own native attempt ID'),
        ]);
    }

    public static function answersheet(int $prova, int $attempt): array {
        global $DB, $USER, $CFG;
        $args = self::validate_parameters(self::answersheet_parameters(), compact('prova', 'attempt'));
        [$p, $context] = self::load($args['prova']);
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $a = $DB->get_record('quiz_attempts', ['id' => $args['attempt'], 'quiz' => $p->quizid,
            'userid' => $USER->id], '*', MUST_EXIST);
        if (!engine::participant($p, $USER->id) && !has_capability('mod/lumen10prova:manage', $context)) {
            throw new \moodle_exception('notavailable', 'mod_lumen10prova');
        }
        $obj = \mod_quiz\quiz_attempt::create($a->id);
        $data = \mod_lumen10prova\appearance::responses($obj);
        return $data + ['state' => $a->state, 'currentpage' => (int)$a->currentpage, 'navigation' => $p->navigation];
    }

    public static function answersheet_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total question count'),
            'state' => new external_value(PARAM_ALPHANUMEXT, 'Attempt state'),
            'currentpage' => new external_value(PARAM_INT, 'Current page'),
            'navigation' => new external_value(PARAM_ALPHA, 'Navigation policy'),
            'rows' => new external_multiple_structure(new external_single_structure([
                'slot' => new external_value(PARAM_INT, 'Question slot'),
                'domid' => new external_value(PARAM_ALPHANUMEXT, 'Question element ID'),
                'number' => new external_value(PARAM_TEXT, 'Question number'),
                'page' => new external_value(PARAM_INT, 'Question page'),
                'type' => new external_value(PARAM_ALPHANUMEXT, 'Question type'),
                'selected' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Own choice')),
                'choices' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Choice label')),
                'answered' => new external_value(PARAM_BOOL, 'Has own response'),
                'flagged' => new external_value(PARAM_BOOL, 'Flagged for review'),
            ])),
        ]);
    }
}
