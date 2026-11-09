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
 * Attempt security.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

/** Server-side hold, independent of grades and of the optional focus telemetry. */
final class attempt_security {
    public const SILENCE_LIMIT = 90;

    public static function initialise(\stdClass $p, \stdClass $a): int {
        $lock = \core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('live-' . $a->id, 5);
        if (!$lock) { throw new \moodle_exception('invalidrequest'); }
        try { return (int)engine::live($p, $a)->securitygeneration; }
        finally { $lock->release(); }
    }

    public static function enabled(\stdClass $p, \stdClass $a): bool {
        return !empty($p->exitlock) && empty($a->preview)
            && in_array($a->state, ['inprogress', 'overdue'], true);
    }

    public static function blocked(\stdClass $p, \stdClass $a, int $now = 0): bool {
        global $DB;
        if (!self::enabled($p, $a)) { return false; }
        $row = $DB->get_record('lumen10prova_live', ['attemptid' => $a->id]);
        return $row && (!empty($row->blockedat) || ($now ?: time()) - $row->lastseen > self::SILENCE_LIMIT);
    }

    public static function reason(\stdClass $p, int $userid): string {
        global $DB;
        if (empty($p->exitlock)) { return ''; }
        foreach ($DB->get_records_select('quiz_attempts',
                'quiz=? AND userid=? AND preview=0 AND state IN (?,?)',
                [$p->quizid, $userid, 'inprogress', 'overdue']) as $a) {
            if (self::blocked($p, $a)) { return 'securityblocked'; }
        }
        return '';
    }

    /** Caller must hold live-{attemptid}; a stale page cannot change a released generation. */
    public static function heartbeat(\stdClass $p, \stdClass $a, \stdClass $row,
            string $event, int $generation, int $now): void {
        global $DB;
        if (!self::enabled($p, $a)) { return; }
        if ($generation !== (int)$row->securitygeneration) {
            throw new \moodle_exception('securitystale', 'mod_lumen10prova');
        }
        if (!$row->blockedat) {
            $reason = in_array($event, ['focus_lost', 'page_left', 'modal_closed', 'print_attempt'], true) ? $event : '';
            if ($now - $row->lastseen > self::SILENCE_LIMIT) { $reason = 'communication_lost'; }
            if ($reason) {
                $row->blockedat = $now;
                $row->blockreason = $reason;
                $DB->update_record('lumen10prova_live', $row);
                engine::audit($p, $a->userid, $a->id, 'attempt_blocked', $reason);
            }
        }
    }

    /** Native web and web-service response writers call the existing login guard. */
    public static function guard_write(\stdClass $p, \stdClass $a): void {
        global $DB;
        if (!self::enabled($p, $a)) { return; }
        if (self::blocked($p, $a)) { throw new \moodle_exception('securityblocked', 'mod_lumen10prova'); }
        $row = $DB->get_record('lumen10prova_live', ['attemptid' => $a->id]);
        $generation = optional_param('l10securitygeneration', 0, PARAM_INT);
        if (!$row || !$generation || $generation !== (int)$row->securitygeneration) {
            throw new \moodle_exception('securitystale', 'mod_lumen10prova');
        }
    }

    public static function resume(\stdClass $p, int $attemptid, string $reason): void {
        global $DB;
        require_capability('mod/lumen10prova:manage', engine::context($p));
        if (trim($reason) === '' || \core_text::strlen($reason) > 500) {
            throw new \moodle_exception('securityreasonrequired', 'mod_lumen10prova');
        }
        $lock = \core\lock\lock_config::get_lock_factory('mod_lumen10prova')->get_lock('live-' . $attemptid, 5);
        if (!$lock) { throw new \moodle_exception('invalidrequest'); }
        try {
            $a = $DB->get_record('quiz_attempts', ['id' => $attemptid, 'quiz' => $p->quizid, 'preview' => 0], '*', MUST_EXIST);
            if (!self::enabled($p, $a) || !self::blocked($p, $a)) {
                throw new \moodle_exception('securitynotblocked', 'mod_lumen10prova');
            }
            $obj = \mod_quiz\quiz_attempt::create($a->id);
            $end = engine::deadline($p, $a, $obj->get_quiz());
            if ($end && time() >= $end) { throw new \moodle_exception('attemptalreadyclosed', 'quiz'); }
            $row = engine::live($p, $a);
            $row->blockedat = 0;
            $row->blockreason = 'none';
            $row->securitygeneration++;
            $row->lastseen = time();
            $DB->update_record('lumen10prova_live', $row);
            engine::audit($p, $a->userid, $a->id, 'attempt_resumed', trim($reason));
        } finally { $lock->release(); }
    }
}
