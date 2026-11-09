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
 * Exam modal.
 *
 * @module     mod_lumen10prova/exam_modal
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';
import {load, text} from 'mod_lumen10prova/strings';
import ModalModule from 'core/modal';
import Notification from 'core/notification';
import Ajax from 'core/ajax';
import {get_string as getString} from 'core/str';

const confirmClose = (question) =>
    new Promise((resolve, reject) => {
        Notification.confirm(
            getString('confirm'),
            question,
            getString('yes'),
            getString('no'),
            () => resolve(true),
            () => resolve(false),
        ).catch(reject);
    });
const Modal = ModalModule.default || ModalModule;
const element = (doc, tag, value, cls) => {
    const e = doc.createElement(tag);
    e.textContent = value;
    if (cls) {
        e.className = cls;
    }
    return e;
};
const choiceKey = (row, input, index) => {
    if (row.type === 'truefalse') {
        return row.choices[input.value === '1' ? 0 : 1];
    }
    return index < 26 ? String.fromCharCode(65 + index) : String(index + 1);
};
const addIdentity = (doc, win, region, options) => {
    const header = doc.createElement('header');
    header.className = 'l10-paper-heading';
    if (options.logo) {
        const img = doc.createElement('img');
        img.src = options.logo;
        img.alt = text('js20');
        header.append(img);
    }
    const identity = doc.createElement('div');
    [
        [text('js21'), options.course],
        [text('js22'), options.title],
        [text('js23'), options.student],
    ].forEach(([k, v]) => identity.append(element(doc, 'p', k + ': ' + v)));
    header.append(identity);
    region.prepend(header);
    if (win === win.top && options.cmid) {
        const back = element(doc, 'a', text('js24'), 'l10-exam-back');
        back.href = Config.wwwroot + '/mod/lumen10prova/view.php?id=' + options.cmid;
        identity.append(back);
    }
    if (options.paper && options.watermark) {
        const water = doc.createElement('img');
        water.src = options.watermark;
        water.alt = '';
        water.setAttribute('aria-hidden', 'true');
        water.className = 'l10-paper-watermark';
        water.style.opacity = options.opacity;
        region.prepend(water);
    }
};
const fitPaper = (frame, options, context) => {
    const doc = frame.contentDocument,
        win = frame.contentWindow;
    if (
        !doc ||
        !win.M ||
        Number(win.M.cfg.contextid) !== context ||
        !win.location.pathname.includes('/mod/quiz/')
    ) {
        return;
    }
    options = {
        ...options,
        preview: !!options.preview || doc.querySelector('meta[name="l10-prova-preview"]')?.content === '1',
    };
    doc.body.classList.add('l10-exam-frame');
    doc.body.classList.toggle('l10-paper', options.paper);
    doc.body.classList.toggle('l10-preview', options.preview);
    // Close hidden native drawers through their controller so mobile resize cannot leave a blocking backdrop.
    win.require(['theme_boost/drawers'], function(DrawersModule) {
        const Drawers = DrawersModule.default || DrawersModule;
        doc.querySelectorAll('.drawer-left' + (options.answersheet ? ',.drawer-right' : '')).forEach((node) =>
            Drawers.getDrawerInstanceForNode(node).closeDrawer({
                focusOnOpenButton: false,
                updatePreferences: false,
            }),
        );
    });

    doc.querySelectorAll('[vw],#vlibras-access-wrapper,.vlibras-widget').forEach((el) => el.remove());
    const region = doc.querySelector('#region-main');
    if (!region || doc.documentElement.dataset.l10PaperReady) {
        return;
    }
    doc.documentElement.dataset.l10PaperReady = '1';
    addIdentity(doc, win, region, options);
    const attemptId = Number(new URL(win.location.href).searchParams.get('attempt'));
    const form = doc.querySelector('#responseform');
    if (!form || !attemptId || !options.answersheet) {
        return;
    }
    doc.body.classList.add('l10-sheet-enhanced');
    const layout = doc.createElement('div');
    layout.className = 'l10-paper-layout';
    const question = doc.createElement('main');
    question.className = 'l10-paper-question';
    const side = doc.createElement('aside');
    side.className = 'l10-paper-side';
    side.setAttribute('aria-label', text('js25'));
    const first = doc.querySelector('.que');
    if (!first) {
        return;
    }
    first.parentNode.insertBefore(layout, first);
    const questionNodes = Array.from(form.querySelectorAll('.que,.submitbtns'));
    questionNodes.forEach((node) => question.append(node));
    layout.append(question, side);
    let timer = options.preview
        ? null
        : doc.querySelector('#quiz-timer-wrapper') || doc.querySelector('#quiz-timer');
    if (!timer && options.preview && options.timelimit) {
        timer = doc.createElement('div');
        timer.append(element(doc, 'h3', text('js26')));
        const seconds = Number(options.timelimit);
        const clock = [Math.floor(seconds / 3600), Math.floor((seconds % 3600) / 60), seconds % 60]
            .map((n) => String(n).padStart(2, '0'))
            .join(':');
        timer.append(element(doc, 'span', clock, 'l10-preview-time-value'));
    }
    if (timer) {
        const box = doc.createElement('div');
        box.className = 'l10-paper-time';
        box.append(timer);
        side.append(box);
    }
    const heading = element(doc, 'h3', text('js27'));
    side.append(heading);
    side.append(element(doc, 'p', text('js28'), 'l10-sheet-help'));
    const sheet = doc.createElement('div');
    sheet.className = 'l10-answer-sheet';
    side.append(sheet);
    const end = doc.querySelector('.endtestlink');
    if (end) {
        const b = element(doc, 'button', text('js29'), 'btn btn-primary w-100 mt-3');
        b.type = 'button';
        b.onclick = () => end.click();
        side.append(b);
    }
    let busy = false,
        stopped = false,
        data = null;
    const decorate = () => {
        form.querySelectorAll('.que.multichoice .answer input').forEach((input) => {
            if (!['radio', 'checkbox'].includes(input.type)) {
                return;
            }
            const row = input.closest('.r0,.r1');
            const letter = row?.querySelector('.answernumber');
            if (letter) {
                const all = Array.from(input.closest('.answer').querySelectorAll('input')).filter((i) =>
                    ['radio', 'checkbox'].includes(i.type),
                );
                const index = all.indexOf(input);
                letter.textContent = index < 26 ? String.fromCharCode(65 + index) : String(index + 1);
            }
            if (row) {
                row.classList.toggle('l10-choice-selected', input.checked);
            }
        });
    };
    const currentChoices = (row) => {
        const q = doc.getElementById(row.domid);
        if (!q) {
            return null;
        }
        const inputs = Array.from(q.querySelectorAll('.answer input')).filter((input) =>
            ['radio', 'checkbox'].includes(input.type),
        );
        if (!inputs.length) {
            return null;
        }
        const selected = [];
        inputs.forEach((input, index) => {
            const key = choiceKey(row, input, index);
            if (input.checked) {
                selected.push(key);
            }
        });
        return selected;
    };
    const render = () => {
        if (!data) {
            return;
        }
        sheet.replaceChildren();
        doc.body.classList.toggle('l10-sheet-enhanced', data.total <= 200);
        data.rows.forEach((row) => {
            const selected = currentChoices(row) || row.selected;
            const line = doc.createElement('div');
            line.className = 'l10-sheet-row';
            line.classList.toggle('l10-sheet-answered', selected.length > 0 || row.answered);
            line.classList.toggle('l10-sheet-flagged', row.flagged);
            const jump = element(doc, 'button', row.number, 'l10-sheet-number');
            jump.type = 'button';
            jump.title = text('jsjumpto', row.number);
            const nav = doc.getElementById('quiznavbutton' + row.slot);
            jump.disabled = !nav || (data.navigation !== 'free' && row.page !== data.currentpage);
            jump.onclick = () => nav.click();
            line.append(jump);
            if (row.choices.length) {
                row.choices.forEach((letter) => {
                    const choice = element(doc, 'button', letter, 'l10-sheet-choice');
                    choice.type = 'button';
                    choice.classList.toggle('selected', selected.includes(letter));
                    choice.setAttribute('aria-label', text('jschoice', {number: row.number, letter}));
                    choice.setAttribute('aria-pressed', selected.includes(letter) ? 'true' : 'false');
                    const q = doc.getElementById(row.domid);
                    if (q) {
                        const title = q.querySelector('.info .no');
                        if (title) {
                            title.textContent = text('jsquestionof', {
                                number: row.number,
                                total: data.total,
                            });
                        }
                    }
                    const inputs = q
                        ? Array.from(q.querySelectorAll('.answer input')).filter((i) =>
                              ['radio', 'checkbox'].includes(i.type),
                          )
                        : [];
                    const index =
                        row.type === 'truefalse'
                            ? inputs.findIndex((i) => choiceKey(row, i, 0) === letter)
                            : row.choices.indexOf(letter);
                    const input = inputs[index];
                    choice.disabled = !input || input.disabled;
                    choice.onclick = () => {
                        input.click();
                        decorate();
                        render();
                    };
                    line.append(choice);
                });
            } else {
                line.append(element(doc, 'span', row.answered ? text('js30') : '—'));
            }
            if (row.flagged) {
                line.append(element(doc, 'span', '⚑'));
            }
            sheet.append(line);
        });
        decorate();
    };
    const refresh = async() => {
        if (stopped || busy) {
            return;
        }
        busy = true;
        try {
            const body = await Ajax.call([
                {
                    methodname: 'mod_lumen10prova_answersheet',
                    args: {prova: options.id, attempt: attemptId},
                },
            ])[0];
            data = body;
            render();
        } catch (e) {
            if (!data) {
                sheet.textContent = text('js31');
            }
            doc.body.classList.remove('l10-sheet-enhanced');
        } finally {
            busy = false;
        }
    };
    form.addEventListener('change', () => {
        decorate();
        render();
    });
    win.addEventListener(
        'pagehide',
        () => {
            stopped = true;
            clearInterval(interval);
        },
        {once: true},
    );
    const interval = setInterval(refresh, 7000);
    void refresh();
};
const init = async(url, context, options) => {
    await load();
    const button = document.getElementById('l10-open-exam');
    if (!button) {
        return;
    }
    button.disabled = false;
    button.dataset.l10ready = '1';
    let opening = false;
    button.addEventListener('click', async() => {
        if (opening) {
            return;
        }
        opening = true;
        try {
            const frame = document.createElement('iframe');
            frame.className = 'l10-exam-iframe';
            frame.title = text('jsframetitle', options.title);
            frame.src = url;
            frame.setAttribute('allow', 'fullscreen');
            const modal = await Modal.create({
                title: options.title,
                body: frame.outerHTML,
                large: true,
                show: false,
                removeOnClose: false,
            });
            await modal.getBodyPromise();
            const root = modal.getRoot()[0];
            root.classList.add('l10-exam-modal');
            root.id = 'l10-exam-dialog';
            const iframe = root.querySelector('iframe');
            iframe.addEventListener('load', () => {
                try {
                    fitPaper(iframe, options, context);
                } catch (e) {
                    Notification.exception(e);
                }
            });
            const active = () =>
                (options.exitlock &&
                    !options.preview &&
                    iframe.contentWindow?.location.pathname === '/mod/quiz/attempt.php') ||
                (options.exitlock &&
                    !options.preview &&
                    iframe.contentWindow?.location.pathname === '/mod/quiz/summary.php');
            const markExit = () => {
                if (active()) {
                    iframe.contentWindow.dispatchEvent(new iframe.contentWindow.Event('l10-prova-leave'));
                }
            };
            const printGuard = (e) => {
                if (!active()) {
                    return;
                }
                if (e.type === 'keydown' && !((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p')) {
                    return;
                }
                e.preventDefault();
                iframe.contentWindow.dispatchEvent(new iframe.contentWindow.Event('beforeprint'));
            };
            if (options.exitlock) {
                document.body.classList.add('l10-protected-exam');
                window.addEventListener('beforeprint', printGuard);
                document.addEventListener('keydown', printGuard);
            }
            const hide = modal.hide.bind(modal);
            modal.hide = async() => {
                const warning = active() ? text('js32') : text('js33');
                if (!(await confirmClose(warning))) {
                    return;
                }
                const save = iframe.contentWindow?.M?.mod_quiz?.autosave;
                if (save?.form) {
                    if (save.dirty && !save.save_transaction) {
                        save.save_changes();
                    }
                    const limit = Date.now() + 8000;
                    while (save.save_transaction && Date.now() < limit) {
                        await new Promise((resolve) => setTimeout(resolve, 100));
                    }
                    if (
                        (save.dirty || save.save_transaction || save.savefailures > 0) &&
                        !(await confirmClose(text('js34')))
                    ) {
                        return;
                    }
                }
                markExit();
                document.body.classList.remove('l10-protected-exam');
                window.removeEventListener('beforeprint', printGuard);
                document.removeEventListener('keydown', printGuard);
                modal.hide = hide;
                modal.destroy();
            };
            modal.show();
        } catch (e) {
            Notification.exception(e);
        } finally {
            opening = false;
        }
    });
};
const enhance = async(context, options) => {
    await load();
    fitPaper({contentDocument: document, contentWindow: window}, options, context);
};
export {init, enhance};
