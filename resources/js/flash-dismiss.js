// Серверные flash с data-auto-dismiss: стек справа, выезд/уезд, автоудаление (по умолчанию 6 с).
const DEFAULT_MS = 6000;
const STACK_ID = 'flash-toast-stack';

function ensureToastStack() {
    let stack = document.getElementById(STACK_ID);
    if (!stack) {
        stack = document.createElement('div');
        stack.id = STACK_ID;
        stack.setAttribute('aria-live', 'polite');
        stack.setAttribute('aria-relevant', 'additions text');
        document.body.appendChild(stack);
    }
    return stack;
}

function slideOutAndRemove(el) {
    el.classList.remove('flash-toast--visible');
    let done = false;
    const finish = () => {
        if (done) {
            return;
        }
        done = true;
        el.removeEventListener('transitionend', onTransitionEnd);
        el.remove();
    };
    const onTransitionEnd = (e) => {
        if (e.propertyName === 'transform') {
            finish();
        }
    };
    el.addEventListener('transitionend', onTransitionEnd);
    window.setTimeout(finish, 600);
}

function mountToast(el, stack, ms) {
    el.classList.add('flash-toast');
    if (!el.classList.contains('alert')) {
        el.classList.add('flash-toast--plain');
    }
    stack.appendChild(el);
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            el.classList.add('flash-toast--visible');
        });
    });
    window.setTimeout(() => slideOutAndRemove(el), ms);
}

function initFlashDismiss() {
    const nodes = Array.from(document.querySelectorAll('[data-auto-dismiss]'));
    if (nodes.length === 0) {
        return;
    }
    const stack = ensureToastStack();
    nodes.forEach((el) => {
        const raw = el.getAttribute('data-auto-dismiss');
        const ms =
            raw === '' || raw === null
                ? DEFAULT_MS
                : Number.parseInt(raw, 10);
        if (!Number.isFinite(ms) || ms <= 0) {
            return;
        }
        mountToast(el, stack, ms);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFlashDismiss);
} else {
    initFlashDismiss();
}

/**
 * Тост из JS (тот же стек и таймер, что у серверных flash).
 *
 * @param {string} message
 * @param {'success'|'error'|'info'|'warning'} [variant]
 */
function showFlashToastMessage(message, variant = 'success') {
    const stack = ensureToastStack();
    const el = document.createElement('div');
    el.setAttribute('role', 'alert');
    el.className = `alert alert-${variant} rounded-none max-w-2xl`;
    el.setAttribute('data-auto-dismiss', '');
    const span = document.createElement('span');
    span.textContent = message;
    el.appendChild(span);
    mountToast(el, stack, DEFAULT_MS);
}

window.showFlashToastMessage = showFlashToastMessage;
