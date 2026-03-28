import {
    installFocusTrap,
    bindDialogUnsavedGuard,
    createGuardedClose,
} from './modal-accessibility.js';

function updateCounter(el, counterEl, opts) {
    if (!el || !counterEl) {
        return;
    }
    const len = el.value.length;
    const max =
        opts.maxLength != null ? opts.maxLength : el.maxLength > 0 ? el.maxLength : null;
    const soft = opts.soft;
    const hard = opts.hard;
    counterEl.textContent = max ? `${len} / ${max}` : String(len);
    counterEl.classList.remove('text-warning', 'text-error', 'text-base-content/50');
    if (max && len > max) {
        counterEl.classList.add('text-error');
    } else if (hard != null && len >= hard) {
        counterEl.classList.add('text-error');
    } else if (soft != null && len >= soft) {
        counterEl.classList.add('text-warning');
    } else {
        counterEl.classList.add('text-base-content/50');
    }
}

function bindCounter(el, counterEl, opts) {
    if (!el || !counterEl) {
        return;
    }
    const run = () => updateCounter(el, counterEl, opts);
    el.addEventListener('input', run);
    run();
}

document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('addStoryModal');
    if (!dialog) {
        return;
    }

    installFocusTrap(dialog);

    const nameInput = document.getElementById('newStoryName');
    const cycleInput = document.getElementById('newStoryCycle');
    const synopsisInput = document.getElementById('newStorySynopsis');
    let snapshot = { name: '', cycle: '', synopsis: '' };

    const isDirty = () =>
        (nameInput?.value ?? '') !== snapshot.name ||
        (cycleInput?.value ?? '') !== snapshot.cycle ||
        (synopsisInput?.value ?? '') !== snapshot.synopsis;

    const guardedClose = createGuardedClose(dialog, isDirty);
    bindDialogUnsavedGuard(dialog, isDirty);

    dialog.querySelectorAll('.story-dialog__scrim').forEach((el) => {
        el.addEventListener('click', guardedClose);
    });
    dialog.querySelectorAll('[data-add-story-close]').forEach((el) => {
        el.addEventListener('click', guardedClose);
    });

    dialog.addEventListener('toggle', (e) => {
        if (e.target !== dialog || !dialog.open) {
            return;
        }
        snapshot = {
            name: nameInput?.value ?? '',
            cycle: cycleInput?.value ?? '',
            synopsis: synopsisInput?.value ?? '',
        };
        requestAnimationFrame(() => nameInput?.focus());
    });

    const nameCounter = document.getElementById('newStoryNameCounter');
    const cycleCounter = document.getElementById('newStoryCycleCounter');
    const synCounter = document.getElementById('newStorySynopsisCounter');
    bindCounter(nameInput, nameCounter, { soft: 200, maxLength: 255 });
    bindCounter(cycleInput, cycleCounter, { maxLength: 255 });
    bindCounter(synopsisInput, synCounter, { soft: 6000, hard: 8000 });

    dialog.querySelector('form.story-dialog__panel')?.addEventListener('submit', () => {
        snapshot = {
            name: nameInput?.value ?? '',
            cycle: cycleInput?.value ?? '',
            synopsis: synopsisInput?.value ?? '',
        };
    });

});
