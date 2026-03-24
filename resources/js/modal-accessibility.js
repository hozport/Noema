/**
 * Фокус-ловушка для <dialog>: Tab циклически ходит по фокусируемым элементам внутри.
 */
const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

function isFocusableVisible(el) {
    if (el.closest('[inert]') || el.hidden) {
        return false;
    }
    const style = window.getComputedStyle(el);
    if (style.visibility === 'hidden' || style.display === 'none') {
        return false;
    }
    return true;
}

function getFocusableIn(root) {
    return Array.from(root.querySelectorAll(FOCUSABLE_SELECTOR)).filter((el) => isFocusableVisible(el));
}

export function installFocusTrap(dialog) {
    if (!dialog) {
        return () => {};
    }

    function onKeydown(e) {
        if (e.key !== 'Tab' || !dialog.open) {
            return;
        }
        const nodes = getFocusableIn(dialog);
        if (nodes.length === 0) {
            return;
        }
        const first = nodes[0];
        const last = nodes[nodes.length - 1];
        if (e.shiftKey) {
            if (document.activeElement === first) {
                e.preventDefault();
                last.focus();
            }
        } else if (document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function onToggle(e) {
        if (e.target !== dialog) {
            return;
        }
        if (dialog.open) {
            dialog.addEventListener('keydown', onKeydown);
        } else {
            dialog.removeEventListener('keydown', onKeydown);
        }
    }

    dialog.addEventListener('toggle', onToggle);
    return () => {
        dialog.removeEventListener('toggle', onToggle);
        dialog.removeEventListener('keydown', onKeydown);
    };
}

/**
 * Escape: нативный cancel у <dialog>. Дополнительно: подтверждение при грязной форме.
 */
export function bindDialogUnsavedGuard(dialog, isDirty) {
    if (!dialog) {
        return;
    }

    dialog.addEventListener('cancel', (e) => {
        if (!isDirty()) {
            return;
        }
        e.preventDefault();
        if (window.confirm('Остались несохранённые изменения. Закрыть без сохранения?')) {
            dialog.close();
        }
    });
}

export function createGuardedClose(dialog, isDirty) {
    return function guardedClose() {
        if (!dialog.open) {
            return;
        }
        if (!isDirty()) {
            dialog.close();
            return;
        }
        if (window.confirm('Остались несохранённые изменения. Закрыть без сохранения?')) {
            dialog.close();
        }
    };
}
