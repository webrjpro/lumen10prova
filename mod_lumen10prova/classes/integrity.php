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
 * Integrity.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova;
defined('MOODLE_INTERNAL') || die();

/** Offline technical certificate. No learner data or licensing network request is involved. */
final class integrity {
    public const PUBLICKEY = 'Ssd0kOEevHlr+arPxw9BOoEvtdvQCPONJM3epv67nqc=';
    public static function verify(): array {
        $root = dirname(__DIR__);
        $file = $root . '/CERTIFICADO_PROTECAO.json';
        $result = ['signature' => false, 'files' => false, 'metadata' => null, 'differences' => []];
        if (!is_file($file) || !function_exists('sodium_crypto_sign_verify_detached')) { return $result; }
        try {
            $certificate = json_decode(file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
            $payload = base64_decode($certificate['payload_base64'], true);
            $signature = base64_decode($certificate['signature_base64'], true);
            $publickey = base64_decode(self::PUBLICKEY, true);
            if ($payload === false || $signature === false || $publickey === false ||
                    strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES || strlen($publickey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ||
                    !sodium_crypto_sign_verify_detached($signature, $payload, $publickey)) { return $result; }
            $data = json_decode($payload, true, 32, JSON_THROW_ON_ERROR);
            if (($data['component'] ?? '') !== 'mod_lumen10prova' || ($data['format'] ?? 0) !== 1) { return $result; }
            $result['signature'] = true; $result['metadata'] = $data;
            foreach ($data['files'] as $relative => $expected) {
                if (str_contains($relative, '..') || str_starts_with($relative, '/') || str_contains($relative, '\\') ||
                        !is_file($root . '/' . $relative) || !hash_equals($expected, hash_file('sha256', $root . '/' . $relative))) {
                    $result['differences'][] = $relative;
                }
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $entry) {
                if (!$entry->isFile()) { continue; }
                $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
                if ($relative !== 'CERTIFICADO_PROTECAO.json' && !isset($data['files'][$relative])) { $result['differences'][] = $relative; }
            }
            $result['files'] = !$result['differences'];
        } catch (\Throwable $e) { return $result; }
        return $result;
    }
}
