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
 * Strings.
 *
 * @module     mod_lumen10prova/strings
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';
const values = {};
let pending;
export const load = () => {
    if (!pending) {
        const keys = [
            'jsconnectionactive',
            'jscommunicationlost',
            'js0',
            'js1',
            'js2',
            'js3',
            'js4',
            'js5',
            'js6',
            'js7',
            'js8',
            'js9',
            'js10',
            'js11',
            'js12',
            'js13',
            'js14',
            'js15',
            'js16',
            'js17',
            'js18',
            'js19',
            'js20',
            'js21',
            'js22',
            'js23',
            'js24',
            'js25',
            'js26',
            'js27',
            'js28',
            'js29',
            'js30',
            'js31',
            'js32',
            'js33',
            'js34',
            'js35',
            'js36',
            'js37',
            'js38',
            'js39',
            'jsremaining',
            'jsjumpto',
            'jschoice',
            'jsquestionof',
            'jsframetitle',
        ];
        pending = getStrings(keys.map((key) => ({key, component: 'mod_lumen10prova'})))
            .then((strings) =>
                keys.forEach((key, index) => {
                    values[key] = strings[index];
                }),
            )
            .catch((error) => {
                pending = null;
                throw error;
            });
    }
    return pending;
};
export const text = (key, args) => {
    let value = values[key] || key;
    if (args !== null && typeof args === 'object') {
        Object.entries(args).forEach(([name, replacement]) => {
            value = value.split('{$a->' + name + '}').join(String(replacement));
        });
    } else if (args !== undefined) {
        value = value.split('{$a}').join(String(args));
    }
    return value;
};
