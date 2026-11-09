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
 * Room.
 *
 * @module     mod_lumen10prova/room
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';
import {load, text} from 'mod_lumen10prova/strings';
import Ajax from 'core/ajax';

/* Lumen10 Prova. GPL-3.0-or-later. Native Quiz performs all response saving. */

const post = (methodname, args) => Ajax.call([{methodname, args}])[0];
const time = (s) => new Date(Math.max(0, s) * 1000).toISOString().slice(11, 19);
const attempt = async(prova, id, options) => {
    await load();
    if (!id) {
        return;
    }
    let stopped = false,
        busy = false,
        deadline = 0,
        offset = 0,
        pending = '',
        lastPosted = 0;
    let navigating = false,
        held = false;
    const shown = new Set();
    const bar = document.createElement('div');
    bar.className = 'l10-status';
    bar.setAttribute('role', 'status');
    const label = document.createElement('span');
    label.textContent = text('js2');
    bar.append(label);
    document.querySelector('#region-main')?.prepend(bar);
    const hold = () => {
        held = true;
        document.body.classList.add('l10-security-held');
        label.textContent = text('js3');
        bar.setAttribute('role', 'alert');
    };
    const securityEvent = (kind) => {
        if (!options.exitlock || options.preview || held || navigating) {
            return;
        }
        hold();
        // Unload needs keepalive; use the same registered External Service and CSRF validation as core/ajax.
        const url = Config.wwwroot + '/lib/ajax/service.php?sesskey=' + encodeURIComponent(Config.sesskey);
        const body = JSON.stringify([
            {
                index: 0,
                methodname: 'mod_lumen10prova_heartbeat',
                args: {prova, attempt: id, event: kind, generation: options.generation},
            },
        ]);
        void fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
            headers: {'Content-Type': 'application/json'},
            body,
        }).catch(() => undefined);
    };
    if (options.exitlock && !options.preview) {
        document.body.classList.add('l10-protected-exam');
        const internal = (url) => {
            try {
                const target = new URL(url, location.href);
                return (
                    target.origin === location.origin &&
                    [
                        '/mod/quiz/attempt.php',
                        '/mod/quiz/summary.php',
                        '/mod/quiz/processattempt.php',
                    ].includes(target.pathname) &&
                    (!target.searchParams.has('attempt') ||
                        Number(target.searchParams.get('attempt')) === Number(id))
                );
            } catch (e) {
                return false;
            }
        };
        document.querySelectorAll('form').forEach((form) => {
            if (!internal(form.action)) {
                return;
            }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'l10securitygeneration';
            input.value = options.generation;
            form.append(input);
            form.addEventListener('submit', () => {
                navigating = true;
            });
        });
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (link && internal(link.href)) {
                navigating = true;
            }
        });
        window.addEventListener('l10-prova-leave', () => securityEvent('modal_closed'));
        window.addEventListener('beforeprint', () => securityEvent('print_attempt'));
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
                e.preventDefault();
                securityEvent('print_attempt');
            }
        });
    }
    if (options.fullscreen) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm btn-outline-secondary ms-2';
        b.textContent = text('js4');
        b.addEventListener('click', () =>
            document.documentElement.requestFullscreen?.().catch(() => undefined),
        );
        bar.append(b);
    }
    const heartbeat = async() => {
        if (stopped || held || busy || !options.monitor || Date.now() - lastPosted < 5500) {
            return;
        }
        busy = true;
        lastPosted = Date.now();
        const sentEvent = pending;
        try {
            const data = await post('mod_lumen10prova_heartbeat', {
                prova,
                attempt: id,
                event: sentEvent,
                generation: options.generation || 0,
            });
            if (data.blocked) {
                hold();
                return;
            }
            if (pending === sentEvent) {
                pending = '';
            }
            offset = data.server - Date.now() / 1000;
            if (!deadline) {
                options.alerts
                    .filter((limit) => limit > data.deadline - data.server)
                    .forEach((limit) => shown.add(limit));
            }
            deadline = data.deadline;
            label.textContent = text('jsconnectionactive');
            if (['finished', 'abandoned'].includes(data.state)) {
                stopped = true;
                location.reload();
            }
        } catch (e) {
            label.textContent = text('jscommunicationlost');
        } finally {
            busy = false;
        }
    };
    const note = (kind) => {
        if (options.logevents) {
            pending = kind;
            void heartbeat();
        }
    };
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && options.exitlock) {
            securityEvent('focus_lost');
        } else {
            note(document.hidden ? 'focus_lost' : 'focus_return');
        }
    });
    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            note('fullscreen_left');
        }
    });
    if (options.blockcopy || options.exitlock) {
        ['copy', 'paste', 'cut', 'contextmenu'].forEach((type) =>
            document.addEventListener(type, (e) => {
                // Accessibility input controls retain selection and navigation. This is a superficial barrier.
                if (type !== 'contextmenu' && e.target.closest('input,textarea,[contenteditable]')) {
                    return;
                }
                e.preventDefault();
                note('clipboard_blocked');
            }),
        );
    }
    const timer = setInterval(() => {
        if (!deadline || stopped) {
            return;
        }
        const remaining = Math.max(0, Math.ceil(deadline - Date.now() / 1000 - offset));
        let crossed = false;
        options.alerts.forEach((limit) => {
            if (remaining <= limit && !shown.has(limit)) {
                shown.add(limit);
                crossed = true;
            }
        });
        if (crossed) {
            const alert = document.createElement('p');
            alert.setAttribute('role', 'alert');
            alert.textContent = text('jsremaining', time(remaining));
            bar.append(alert);
        }
        if (!remaining) {
            void heartbeat();
        }
    }, 1000);
    const interval = setInterval(heartbeat, 15000);
    // Moodle 5.1 native autosave remains the sole writer. Shorten its per-page debounce
    // without changing the institution-wide Quiz setting or creating a second writer.
    let checks = 0;
    const saveSetup = setInterval(() => {
        const autosave = window.M?.mod_quiz?.autosave;
        if (autosave?.form && typeof autosave.save_changes === 'function') {
            autosave.delay = Math.min(autosave.delay, 5000);
            clearInterval(saveSetup);
        } else if (++checks >= 50 || stopped) {
            clearInterval(saveSetup);
        }
    }, 200);
    if (!options.monitor) {
        label.textContent = options.preview ? text('js5') : text('js6');
    }
    window.addEventListener('online', heartbeat);
    window.addEventListener(
        'pagehide',
        () => {
            securityEvent('page_left');
            stopped = true;
            clearInterval(timer);
            clearInterval(interval);
            clearInterval(saveSetup);
        },
        {once: true},
    );
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
            location.reload();
        }
    });
    void heartbeat();
};
const monitor = async(id) => {
    await load();
    let stopped = false,
        busy = false;
    const labels = {
        notstarted: text('js7'),
        inprogress: text('js8'),
        overdue: text('js9'),
        finished: text('js10'),
        abandoned: text('js11'),
        unresponsive: text('js12'),
        submitted: text('js13'),
        blocked: text('js14'),
    };
    const refresh = async() => {
        if (stopped || busy) {
            return;
        }
        busy = true;
        try {
            const data = await post('mod_lumen10prova_poll', {id});
            const tbody = document.querySelector('#l10-room-rows');
            tbody.replaceChildren();
            document.querySelector('#l10-room-count').textContent = data.counts
                .map(({status, count}) => (labels[status] || status) + ': ' + count)
                .join(' · ');
            data.rows.forEach((row) => {
                const tr = document.createElement('tr');
                [
                    row.name,
                    labels[row.status] || row.status,
                    time(row.remaining),
                    row.focuschanges + ' / ' + row.reconnections,
                    row.ipchanges + ' / ' + row.devicechanges,
                ].forEach((value) => {
                    const td = document.createElement('td');
                    td.textContent = value;
                    tr.append(td);
                });
                const actions = document.createElement('td');
                if (data.canrelease && row.status === 'blocked') {
                    const f = document.createElement('form');
                    f.method = 'post';
                    f.action = Config.wwwroot + '/mod/lumen10prova/action.php';
                    Object.entries({
                        id,
                        attempt: row.attempt,
                        action: 'resume',
                        sesskey: Config.sesskey,
                    }).forEach(([key, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        f.append(input);
                    });
                    const reason = document.createElement('input');
                    reason.name = 'reason';
                    reason.required = true;
                    reason.maxLength = 500;
                    reason.setAttribute('aria-label', text('js15'));
                    reason.placeholder = text('js15');
                    const b = document.createElement('button');
                    b.type = 'submit';
                    b.className = 'btn btn-sm btn-primary';
                    b.textContent = text('js16');
                    f.append(reason, b);
                    actions.append(f);
                }
                if (data.canrelease && row.status === 'notstarted') {
                    const f = document.createElement('form');
                    f.method = 'post';
                    f.action = Config.wwwroot + '/mod/lumen10prova/action.php';
                    Object.entries({
                        id,
                        userid: row.userid,
                        action: 'release',
                        sesskey: Config.sesskey,
                    }).forEach(([key, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        f.append(input);
                    });
                    const b = document.createElement('button');
                    b.type = 'submit';
                    b.className = 'btn btn-sm btn-primary';
                    b.textContent = text('js17');
                    f.append(b);
                    actions.append(f);
                }
                if (row.attempt) {
                    const a = document.createElement('a');
                    a.textContent = text('js18');
                    a.href =
                        Config.wwwroot + '/mod/lumen10prova/reports.php?id=' + id + '&attempt=' + row.attempt;
                    actions.append(a);
                }
                tr.append(actions);
                tbody.append(tr);
            });
            document.querySelector('#l10-room-error').textContent = '';
        } catch (e) {
            document.querySelector('#l10-room-error').textContent = text('js19');
        } finally {
            busy = false;
        }
    };
    const timer = setInterval(refresh, 10000);
    window.addEventListener(
        'pagehide',
        () => {
            stopped = true;
            clearInterval(timer);
        },
        {once: true},
    );
    void refresh();
};

export {attempt, monitor};
