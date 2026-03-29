document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('biography-create-open')?.addEventListener('click', () => {
        document.getElementById('biography-create-dialog')?.showModal();
    });

    document.getElementById('biography-open-edit')?.addEventListener('click', () => {
        document.getElementById('biography-edit-dialog')?.showModal();
    });

    document.querySelectorAll('[data-biography-dialog-close]').forEach((el) => {
        el.addEventListener('click', () => {
            const dialog = el.closest('dialog');
            dialog?.close();
        });
    });

    document.querySelectorAll('[data-bio-kinship-select]').forEach((sel) => {
        const suffix = sel.getAttribute('data-bio-kinship-select');
        if (suffix) {
            initBiographyKinshipUI(sel, suffix);
        }
    });

    document.querySelectorAll('[data-bio-faction-other-select]').forEach((sel) => {
        const suffix = sel.getAttribute('data-bio-faction-other-select');
        const wrapPrefix = sel.getAttribute('data-bio-faction-other-wrap-prefix');
        if (suffix && wrapPrefix) {
            initBiographyFactionOtherToggle(sel, suffix, wrapPrefix);
        }
    });

    initBiographyEventsBlock();
});

/**
 * @param {HTMLSelectElement} sel
 * @param {string} suffix
 * @param {string} wrapPrefix id prefix without suffix, e.g. bio-race-other-wrap
 */
function initBiographyFactionOtherToggle(sel, suffix, wrapPrefix) {
    const wrap = document.getElementById(`${wrapPrefix}-${suffix}`);
    if (!wrap) {
        return;
    }
    function toggle() {
        if (sel.value === 'other') {
            wrap.classList.remove('hidden');
        } else {
            wrap.classList.add('hidden');
        }
    }
    sel.addEventListener('change', toggle);
    toggle();
}

/**
 * @param {HTMLSelectElement} multiSelect
 * @param {string} suffix
 */
function initBiographyKinshipUI(multiSelect, suffix) {
    const container = document.getElementById(`bio-rel-kinship-${suffix}`);
    const labelsEl = document.getElementById(`bio-kinship-labels-${suffix}`);
    if (!container || !labelsEl) {
        return;
    }

    /** @type {Record<string, string>} */
    let labels = {};
    try {
        labels = JSON.parse(labelsEl.textContent || '{}');
    } catch {
        return;
    }

    /** @type {Record<string, string|null>} */
    let initialKinship = {};
    /** @type {Record<string, string|null>} */
    let initialCustom = {};
    try {
        initialKinship = JSON.parse(container.dataset.initialKinship || '{}');
    } catch {
        initialKinship = {};
    }
    try {
        initialCustom = JSON.parse(container.dataset.initialCustom || '{}');
    } catch {
        initialCustom = {};
    }

    const customKey = 'custom';

    function readPersistedFromDom() {
        /** @type {Record<string, string>} */
        const kin = {};
        /** @type {Record<string, string>} */
        const cus = {};
        container.querySelectorAll('select[name^="relative_kinship["]').forEach((s) => {
            const m = s.name.match(/relative_kinship\[(\d+)\]/);
            if (m) {
                kin[m[1]] = s.value;
            }
        });
        container.querySelectorAll('input.bio-rel-kinship-custom-input').forEach((inp) => {
            const m = inp.name.match(/relative_kinship_custom\[(\d+)\]/);
            if (m) {
                cus[m[1]] = inp.value;
            }
        });

        return { kin, cus };
    }

    function toggleCustomInput(row, value) {
        const inp = row.querySelector('.bio-rel-kinship-custom-input');
        if (!inp) {
            return;
        }
        if (value === customKey) {
            inp.classList.remove('hidden');
            inp.removeAttribute('aria-hidden');
        } else {
            inp.classList.add('hidden');
            inp.setAttribute('aria-hidden', 'true');
        }
    }

    function renderRows() {
        const { kin: prevKin, cus: prevCus } = readPersistedFromDom();
        const selected = Array.from(multiSelect.selectedOptions);
        container.innerHTML = '';
        if (selected.length === 0) {
            const hint = document.createElement('p');
            hint.className = 'text-xs text-base-content/50';
            hint.textContent = 'Выберите родственников в списке выше — здесь появятся поля степени родства.';
            container.appendChild(hint);

            return;
        }

        selected.forEach((opt) => {
            const id = opt.value;
            const name = opt.textContent || '';
            const row = document.createElement('div');
            row.className =
                'flex flex-wrap items-end gap-x-3 gap-y-2 border-b border-base-300/30 pb-3 last:border-0 last:pb-0';
            const nameSpan = document.createElement('span');
            nameSpan.className = 'text-sm font-medium text-base-content shrink-0 min-w-[6rem] max-w-[14rem]';
            nameSpan.textContent = name;

            const sel = document.createElement('select');
            sel.name = `relative_kinship[${id}]`;
            sel.className =
                'bio-kinship-degree-select select select-bordered select-sm rounded-none bg-base-200 border-base-300 flex-1 min-w-[12rem] max-w-md';
            sel.setAttribute('aria-label', `Степень родства: ${name}`);

            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = '— Степень родства —';
            sel.appendChild(emptyOpt);

            Object.keys(labels).forEach((key) => {
                const o = document.createElement('option');
                o.value = key;
                o.textContent = labels[key];
                sel.appendChild(o);
            });

            const ik =
                prevKin[id] ??
                prevKin[String(id)] ??
                initialKinship[id] ??
                initialKinship[String(id)] ??
                '';
            const ic =
                prevCus[id] ?? prevCus[String(id)] ?? initialCustom[id] ?? initialCustom[String(id)] ?? '';
            if (ik && Object.prototype.hasOwnProperty.call(labels, ik)) {
                sel.value = ik;
            }

            const customInp = document.createElement('input');
            customInp.type = 'text';
            customInp.name = `relative_kinship_custom[${id}]`;
            customInp.className =
                'input input-bordered input-sm rounded-none bg-base-200 border-base-300 flex-1 min-w-[10rem] max-w-md bio-rel-kinship-custom-input';
            customInp.placeholder = 'Свой вариант';
            customInp.value = ic !== null && ic !== undefined ? String(ic) : '';
            customInp.setAttribute('aria-label', `Свой вариант степени родства: ${name}`);
            if (sel.value !== customKey) {
                customInp.classList.add('hidden');
                customInp.setAttribute('aria-hidden', 'true');
            }

            sel.addEventListener('change', () => {
                toggleCustomInput(row, sel.value);
            });

            row.appendChild(nameSpan);
            row.appendChild(sel);
            row.appendChild(customInp);
            container.appendChild(row);
            toggleCustomInput(row, sel.value);
        });
    }

    multiSelect.addEventListener('change', renderRows);
    renderRows();
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * @param {string} url
 * @param {RequestInit} options
 */
async function biographyApi(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };
    const res = await fetch(url, { ...options, headers });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const msg =
            data.message ||
            (data.errors && Object.values(data.errors).flat().join(' ')) ||
            res.statusText;
        throw new Error(msg || 'Ошибка запроса');
    }
    return data;
}

/**
 * @param {object} raw
 */
function normalizeEvent(raw) {
    return {
        id: raw.id,
        title: raw.title,
        year: raw.epoch_year !== undefined ? raw.epoch_year : raw.year ?? null,
        year_end: raw.year_end ?? null,
        month: raw.month ?? 1,
        day: raw.day ?? 1,
        body: raw.body ?? '',
        breaks_line: !!raw.breaks_line,
        on_timeline: !!raw.on_timeline,
    };
}

function initBiographyEventsBlock() {
    const root = document.getElementById('biography-events-root');
    if (!root) {
        return;
    }

    const noticeEl = document.getElementById('biography-events-notice');
    const biographyName = root.dataset.biographyName || '';
    let timelineLines = [];
    try {
        timelineLines = JSON.parse(root.dataset.timelineLines || '[]');
    } catch {
        timelineLines = [];
    }
    if (!Array.isArray(timelineLines)) {
        timelineLines = [];
    }

    let events = [];
    try {
        const initial = JSON.parse(root.dataset.biographyEvents || '[]');
        events = Array.isArray(initial) ? initial.map(normalizeEvent) : [];
    } catch {
        events = [];
    }

    const storeUrl = root.dataset.eventsStoreUrl || '';
    const updateBase = root.dataset.eventUpdateBase || '';
    const createLineUrl = root.dataset.createLineUrl || '';
    const removeLineUrl = root.dataset.removeLineUrl || '';
    const lineOnTimeline = root.dataset.biographyLineOnTimeline === '1';
    const pushEventUrl = root.dataset.pushEventUrl || '';

    const listEl = root.querySelector('.biography-events-list');
    const emptyEl = root.querySelector('.biography-events-empty');
    const countEl = root.querySelector('.biography-events-count');
    const template = document.getElementById('biography-event-row-template');
    const form = root.querySelector('.biography-events-add-form');
    const sendDialog = document.getElementById('biography-send-timeline-dialog');
    const createLineDialog = document.getElementById('biography-create-line-dialog');

    const sendRadios = sendDialog?.querySelector('.biography-send-timeline-radios');
    const sendEventLabel = sendDialog?.querySelector('.biography-send-timeline-event-label');
    const sendConfirm = sendDialog?.querySelector('.biography-send-timeline-confirm');
    const createLineLead = createLineDialog?.querySelector('.biography-create-line-lead');
    const createLineBtn = root.querySelector('.biography-events-create-line');
    const createLineConfirm = createLineDialog?.querySelector('.biography-create-line-confirm');
    const createLineColor = createLineDialog?.querySelector('.biography-create-line-color');

    let pendingSendEventId = null;

    function announce(msg, isError = false) {
        if (!noticeEl) {
            return;
        }
        noticeEl.textContent = msg;
        noticeEl.classList.toggle('hidden', !msg);
        noticeEl.classList.toggle('text-error', isError);
        noticeEl.classList.toggle('text-success', !isError);
        if (msg) {
            window.setTimeout(() => {
                noticeEl.classList.add('hidden');
                noticeEl.textContent = '';
            }, 6000);
        }
    }

    function formatWhen(ev) {
        const y = ev.year;
        const ye = ev.year_end;
        const datePart =
            ev.month && ev.day && (ev.month !== 1 || ev.day !== 1)
                ? ` · ${String(ev.month).padStart(2, '0')}.${String(ev.day).padStart(2, '0')}`
                : '';
        if (y != null && ye != null && ye !== y) {
            return `${y} — ${ye} г.${datePart}`;
        }
        if (y != null) {
            return `${y} г.${datePart}`;
        }
        return 'Без года на шкале мира';
    }

    function escapeText(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function pluralRu(n, one, few, many) {
        const m = n % 100;
        const m1 = n % 10;
        if (m >= 11 && m <= 19) {
            return many;
        }
        if (m1 === 1) {
            return one;
        }
        if (m1 >= 2 && m1 <= 4) {
            return few;
        }
        return many;
    }

    function render() {
        const n = events.length;
        if (countEl) {
            countEl.textContent = `${n} ${pluralRu(n, 'событие', 'события', 'событий')}`;
        }
        if (!listEl || !emptyEl || !template) {
            return;
        }
        listEl.innerHTML = '';
        if (n === 0) {
            emptyEl.classList.remove('hidden');
            listEl.classList.add('hidden');
            return;
        }
        emptyEl.classList.add('hidden');
        listEl.classList.remove('hidden');

        events.forEach((ev) => {
            const node = template.content.cloneNode(true);
            const li = node.querySelector('li');
            li.dataset.eventId = String(ev.id);
            node.querySelector('.biography-events-item-title').textContent = ev.title;
            const bodyEl = node.querySelector('.biography-events-item-body');
            const whenEl = node.querySelector('.biography-events-item-when');
            const onTl = node.querySelector('.biography-events-item-on-timeline');
            const breaksEl = node.querySelector('.biography-events-item-breaks');
            whenEl.textContent = formatWhen(ev);
            if (ev.breaks_line) {
                breaksEl?.classList.remove('hidden');
            } else {
                breaksEl?.classList.add('hidden');
            }
            if (ev.on_timeline) {
                onTl?.classList.remove('hidden');
            } else {
                onTl?.classList.add('hidden');
            }
            if (ev.body && String(ev.body).trim()) {
                bodyEl.textContent = ev.body;
                bodyEl.classList.remove('hidden');
            } else {
                bodyEl.textContent = '';
                bodyEl.classList.add('hidden');
            }

            const sendBtn = li.querySelector('.biography-events-send');
            if (ev.on_timeline) {
                sendBtn.disabled = true;
                sendBtn.textContent = 'Уже на таймлайне';
                sendBtn.classList.add('btn-disabled', 'opacity-60');
            } else {
                sendBtn.addEventListener('click', () => openSendDialog(ev));
            }
            li.querySelector('.biography-events-delete')?.addEventListener('click', () => deleteEvent(ev));

            listEl.appendChild(node);
        });
    }

    async function deleteEvent(ev) {
        if (
            !confirm(
                'Удалить факт из биографии? Запись на таймлайне останется (связь снимется). Чтобы убрать точку с линии, удалите её в разделе «Таймлайн».',
            )
        ) {
            return;
        }
        try {
            await biographyApi(`${updateBase}/${ev.id}`, { method: 'DELETE' });
            events = events.filter((e) => e.id !== ev.id);
            render();
            announce('Факт удалён из биографии.');
        } catch (e) {
            announce(e.message || 'Не удалось удалить', true);
        }
    }

    function openSendDialog(ev) {
        if (timelineLines.length === 0) {
            announce('В мире пока нет линий таймлайна.', true);
            return;
        }
        pendingSendEventId = ev.id;
        if (sendEventLabel) {
            sendEventLabel.textContent = `Событие: «${ev.title}»`;
        }
        if (sendRadios) {
            sendRadios.innerHTML = '';
            const name = `timeline_line_${ev.id}`;
            const defaultIdx = Math.max(
                0,
                timelineLines.findIndex((l) => l.is_main),
            );
            timelineLines.forEach((line, idx) => {
                const wrap = document.createElement('label');
                wrap.className =
                    'flex items-start gap-3 cursor-pointer rounded-none border border-transparent hover:border-base-300 hover:bg-base-200/40 p-2 -m-2';
                const input = document.createElement('input');
                input.type = 'radio';
                input.name = name;
                input.value = String(line.id);
                input.className = 'radio radio-sm mt-1 rounded-none';
                input.checked = idx === defaultIdx;
                const span = document.createElement('span');
                span.className = 'min-w-0';
                const title = document.createElement('span');
                title.className = 'block text-sm font-medium text-base-content';
                title.textContent = line.label;
                const desc = document.createElement('span');
                desc.className = 'block text-xs text-base-content/50 mt-0.5';
                desc.textContent = line.description || '';
                span.appendChild(title);
                span.appendChild(desc);
                wrap.appendChild(input);
                wrap.appendChild(span);
                sendRadios.appendChild(wrap);
            });
        }
        sendDialog?.showModal();
    }

    sendConfirm?.addEventListener('click', async () => {
        const ev = events.find((x) => x.id === pendingSendEventId);
        const checked = sendDialog?.querySelector('input[type="radio"]:checked');
        const lineId = checked?.value;
        sendDialog?.close();
        pendingSendEventId = null;
        if (!ev || !lineId || !pushEventUrl) {
            return;
        }
        if (ev.year == null) {
            announce('Укажите год на шкале мира у события, чтобы разместить его на таймлайне.', true);
            return;
        }
        try {
            await biographyApi(pushEventUrl, {
                method: 'POST',
                body: JSON.stringify({
                    timeline_line_id: Number.parseInt(lineId, 10),
                    biography_event_id: ev.id,
                }),
            });
            ev.on_timeline = true;
            render();
            announce('Событие добавлено на таймлайн.');
        } catch (e) {
            announce(e.message || 'Ошибка', true);
        }
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const titleInput = form.querySelector('[name="title"]');
        const yearInput = form.querySelector('[name="year"]');
        const yearEndInput = form.querySelector('[name="year_end"]');
        const monthInput = form.querySelector('[name="month"]');
        const dayInput = form.querySelector('[name="day"]');
        const bodyInput = form.querySelector('[name="body"]');
        const breaksInput = form.querySelector('#biography-event-breaks-line');
        const title = String(titleInput?.value || '').trim();
        if (!title) {
            titleInput?.focus();
            return;
        }
        const yearRaw = String(yearInput?.value || '').trim();
        const yearEndRaw = String(yearEndInput?.value || '').trim();
        const y = yearRaw === '' ? null : Number.parseInt(yearRaw, 10);
        const ye = yearEndRaw === '' ? null : Number.parseInt(yearEndRaw, 10);
        const month = Number.parseInt(String(monthInput?.value || '1'), 10);
        const day = Number.parseInt(String(dayInput?.value || '1'), 10);
        const body = String(bodyInput?.value || '').trim();

        if (!storeUrl) {
            return;
        }

        try {
            const payload = {
                title,
                epoch_year: y !== null && Number.isFinite(y) ? y : null,
                year_end: ye !== null && Number.isFinite(ye) ? ye : null,
                month: Number.isFinite(month) ? month : 1,
                day: Number.isFinite(day) ? day : 1,
                body: body || null,
                breaks_line: !!(breaksInput && breaksInput.checked),
            };
            const data = await biographyApi(storeUrl, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            const ne = normalizeEvent(data.event);
            events = [...events, ne];
            render();
            if (titleInput) {
                titleInput.value = '';
            }
            if (yearInput) {
                yearInput.value = '';
            }
            if (yearEndInput) {
                yearEndInput.value = '';
            }
            if (monthInput) {
                monthInput.value = '1';
            }
            if (dayInput) {
                dayInput.value = '1';
            }
            if (bodyInput) {
                bodyInput.value = '';
            }
            if (breaksInput) {
                breaksInput.checked = false;
            }
            announce('Событие сохранено.');
        } catch (err) {
            announce(err.message || 'Не удалось сохранить', true);
        }
    });

    root.querySelector('.biography-events-clear-draft')?.addEventListener('click', () => {
        form?.querySelector('[name="title"]') && (form.querySelector('[name="title"]').value = '');
        form?.querySelector('[name="year"]') && (form.querySelector('[name="year"]').value = '');
        form?.querySelector('[name="year_end"]') && (form.querySelector('[name="year_end"]').value = '');
        const m = form?.querySelector('[name="month"]');
        const d = form?.querySelector('[name="day"]');
        if (m) {
            m.value = '1';
        }
        if (d) {
            d.value = '1';
        }
        form?.querySelector('[name="body"]') && (form.querySelector('[name="body"]').value = '');
        const br = form?.querySelector('#biography-event-breaks-line');
        if (br) {
            br.checked = false;
        }
    });

    createLineBtn?.addEventListener('click', () => {
        if (lineOnTimeline && removeLineUrl) {
            if (
                !confirm(
                    'Удалить линию персонажа с таймлайна? События на этой линии исчезнут с таймлайна. Записи в биографии останутся — их можно снова вынести на линию позже.',
                )
            ) {
                return;
            }
            (async () => {
                try {
                    const data = await biographyApi(removeLineUrl, { method: 'DELETE' });
                    announce(data.message || 'Линия убрана с таймлайна.');
                    window.location.reload();
                } catch (e) {
                    announce(e.message || 'Ошибка', true);
                }
            })();
            return;
        }
        const n = events.filter((e) => e.year != null && !e.on_timeline).length;
        if (createLineLead) {
            createLineLead.innerHTML = `Будет создана линия «${escapeText(biographyName || 'Персонаж')}» и на неё выставлены <strong>${n}</strong> ${pluralRu(n, 'событие с годом', 'события с годом', 'событий с годом')} (ещё не на таймлайне).`;
        }
        createLineDialog?.showModal();
    });

    createLineConfirm?.addEventListener('click', async () => {
        createLineDialog?.close();
        if (!createLineUrl) {
            return;
        }
        const color = createLineColor?.value || '#457B9D';
        try {
            await biographyApi(createLineUrl, {
                method: 'POST',
                body: JSON.stringify({ color }),
            });
            announce('Линия создана на таймлайне, события размещены.');
            window.location.reload();
        } catch (e) {
            announce(e.message || 'Ошибка', true);
        }
    });

    render();
}
