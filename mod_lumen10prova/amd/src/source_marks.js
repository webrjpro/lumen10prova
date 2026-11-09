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
 * Source marks.
 *
 * @module     mod_lumen10prova/source_marks
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {load, text} from 'mod_lumen10prova/strings';

const init = async(marks) => {
    await load();
    marks.forEach((mark) => {
        const question = document.getElementById(mark.domid);
        const content = question?.querySelector('.qtext');
        if (!content || question.querySelector('.l10-source-mark')) {
            return;
        }
        const box = document.createElement('aside');
        box.className = 'l10-source-mark';
        box.setAttribute('aria-label', text('js35'));
        const img = document.createElement('img');
        img.src = mark.image;
        img.alt = text('js36');
        img.width = mark.size;
        img.height = mark.size;
        img.style.width = mark.size + 'px';
        img.style.height = mark.size + 'px';
        const notice = document.createElement('div');
        const title = document.createElement('strong');
        title.textContent = text('js37');
        const message = document.createElement('p');
        message.textContent = text('js38');
        const ref = document.createElement('small');
        ref.textContent = text('js39') + mark.token;
        notice.append(title, message, ref);
        box.append(img, notice);
        content.prepend(box);
        const stamp = document.createElement('span');
        stamp.className = 'l10-question-stamp';
        stamp.setAttribute('aria-hidden', 'true');
        stamp.textContent = mark.token;
        content.append(stamp);
    });
};
export {init};
