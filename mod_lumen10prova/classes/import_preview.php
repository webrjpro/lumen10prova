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
 * Import preview.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

/** User-scoped, expiring import previews. Session data contains no question payload. */
final class import_preview {
    private static function cache(): \cache {
        global $USER;
        return \cache::make('mod_lumen10prova', 'importpreview', ['userid' => (int)$USER->id]);
    }
    public static function store(int $provaid, array $rows): string {
        global $USER;
        $token = bin2hex(random_bytes(24));
        self::cache()->set($token, [
            'provaid' => $provaid, 'userid' => (int)$USER->id, 'expires' => time() + 1800, 'rows' => $rows,
        ]);
        return $token;
    }

    public static function get(int $provaid, string $token): ?array {
        global $USER;
        if (!preg_match('/^[a-f0-9]{48}$/D', $token)) {
            return null;
        }
        $cache = self::cache();
        $pending = $cache->get($token);
        if (!$pending || $pending['provaid'] !== $provaid || $pending['userid'] !== (int)$USER->id ||
                $pending['expires'] < time()) {
            return null;
        }
        return $pending;
    }

    public static function delete(string $token): void {
        self::cache()->delete($token);
    }
}
