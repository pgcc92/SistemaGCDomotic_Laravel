import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import 'preline';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.start();

function ensureToastContainer() {
    let container = document.getElementById('gc-toasts');
    if (!container) {
        container = document.createElement('div');
        container.id = 'gc-toasts';
        container.className = 'fixed inset-x-3 top-3 z-[100] space-y-3 sm:left-auto sm:right-4 sm:top-4';
        document.body.appendChild(container);
    }
    return container;
}

function toastIconSvg(type) {
    const common = 'h-5 w-5';
    if (type === 'success') {
        return `<svg class="${common}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`;
    }
    if (type === 'warning') {
        return `<svg class="${common}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.518 11.59c.75 1.334-.213 2.99-1.742 2.99H3.48c-1.53 0-2.492-1.656-1.743-2.99l6.52-11.59zM11 14a1 1 0 10-2 0 1 1 0 002 0zm-1-2a1 1 0 01-1-1V8a1 1 0 012 0v3a1 1 0 01-1 1z" clip-rule="evenodd"/></svg>`;
    }
    if (type === 'error') {
        return `<svg class="${common}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2.707-10.707a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 001.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293z" clip-rule="evenodd"/></svg>`;
    }
    return `<svg class="${common}" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-8-3a1 1 0 00-1 1v2a1 1 0 002 0V8a1 1 0 00-1-1zm0 6a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>`;
}

function toastClasses(type) {
    if (type === 'success') return { bg: 'bg-emerald-50', border: 'border-emerald-200', icon: 'text-emerald-600', title: 'text-emerald-900' };
    if (type === 'warning') return { bg: 'bg-amber-50', border: 'border-amber-200', icon: 'text-amber-600', title: 'text-amber-900' };
    if (type === 'error') return { bg: 'bg-rose-50', border: 'border-rose-200', icon: 'text-rose-600', title: 'text-rose-900' };
    return { bg: 'bg-slate-50', border: 'border-slate-200', icon: 'text-slate-600', title: 'text-slate-900' };
}

function createToast({ type = 'info', title = '', message = '', timeoutMs = 6500 } = {}) {
    const container = ensureToastContainer();
    const el = document.createElement('div');
    const c = toastClasses(type);
    const id = `gc-toast-${crypto?.randomUUID?.() || Math.random().toString(16).slice(2)}`;
    el.id = id;

    // Preline-style toast markup + HSRemoveElement support
    el.className = `pointer-events-auto w-[min(92vw,420px)] rounded-2xl border ${c.border} ${c.bg} shadow-xl p-4 opacity-0 translate-x-2 transition duration-200 ease-out`;
    el.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="mt-0.5 ${c.icon}">${toastIconSvg(type)}</div>
            <div class="min-w-0 flex-1">
                ${title ? `<div class="text-sm font-semibold ${c.title}">${escapeHtml(title)}</div>` : ``}
                ${message ? `<div class="mt-1 text-sm text-slate-700 whitespace-pre-line">${escapeHtml(message)}</div>` : ``}
            </div>
            <button type="button"
                    class="inline-flex rounded-lg p-1.5 text-slate-500 hover:bg-white/70 focus:outline-none focus:ring-2 focus:ring-primary/30"
                    data-hs-remove-element="#${id}"
                    data-hs-remove-element-options='{"removeTargetAnimationClass":"hs-removing"}'
                    aria-label="Cerrar">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    `;

    container.appendChild(el);
    // init Preline remove-element for dynamically added button
    if (window.HSStaticMethods?.autoInit) {
        window.HSStaticMethods.autoInit('remove-element');
    }
    // animate in
    window.requestAnimationFrame(() => {
        el.classList.remove('opacity-0', 'translate-x-2');
        el.classList.add('opacity-100', 'translate-x-0');
    });

    if (timeoutMs && timeoutMs > 0) {
        window.setTimeout(() => {
            // trigger remove-element programmatically by clicking close
            const btn = el.querySelector('[data-hs-remove-element]');
            if (btn) btn.click();
            else el.remove();
        }, timeoutMs);
    }
    return { remove: () => el.remove() };
}

function escapeHtml(input) {
    const s = String(input ?? '');
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function ensureDialogRoot() {
    let root = document.getElementById('gc-dialog-root');
    if (!root) {
        root = document.createElement('div');
        root.id = 'gc-dialog-root';
        document.body.appendChild(root);
    }
    return root;
}

function openDialog(options = {}) {
    const settings = typeof options === 'string' ? { message: options } : options;
    const {
        title = 'Confirmar acción',
        message = '',
        confirmText = 'Aceptar',
        cancelText = 'Cancelar',
        tone = 'primary',
        showCancel = false,
    } = settings;
    const buttonClass = tone === 'danger'
        ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-300'
        : 'bg-primary hover:bg-primary/90 focus:ring-primary/30';

    return new Promise((resolve) => {
        const root = ensureDialogRoot();
        root.innerHTML = `
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-3 sm:items-center" role="dialog" aria-modal="true">
                <div data-gc-dialog-backdrop class="absolute inset-0 bg-slate-950/45 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl ${tone === 'danger' ? 'bg-rose-50 text-rose-600' : 'bg-primary/10 text-primary'}">
                            ${toastIconSvg(tone === 'danger' ? 'warning' : 'info')}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold text-slate-900">${escapeHtml(title)}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">${escapeHtml(message)}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        ${showCancel ? `<button data-gc-dialog-cancel type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">${escapeHtml(cancelText)}</button>` : ''}
                        <button data-gc-dialog-confirm type="button" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white focus:outline-none focus:ring-2 ${buttonClass}">${escapeHtml(confirmText)}</button>
                    </div>
                </div>
            </div>
        `;
        const close = (result) => {
            document.removeEventListener('keydown', onKeydown);
            root.innerHTML = '';
            resolve(result);
        };
        const onKeydown = (event) => {
            if (event.key === 'Escape') close(false);
        };
        document.addEventListener('keydown', onKeydown);
        root.querySelector('[data-gc-dialog-confirm]')?.addEventListener('click', () => close(true));
        root.querySelector('[data-gc-dialog-cancel]')?.addEventListener('click', () => close(false));
        root.querySelector('[data-gc-dialog-backdrop]')?.addEventListener('click', () => close(false));
        root.querySelector('[data-gc-dialog-confirm]')?.focus();
    });
}

window.GCDialog = {
    alert: (options) => openDialog({ ...(typeof options === 'string' ? { message: options } : options), showCancel: false }),
    confirm: (options) => openDialog({ ...(typeof options === 'string' ? { message: options } : options), showCancel: true }),
};

window.GCToast = {
    info: (title, message, opts) => createToast({ type: 'info', title, message, ...(opts || {}) }),
    success: (title, message, opts) => createToast({ type: 'success', title, message, ...(opts || {}) }),
    warning: (title, message, opts) => createToast({ type: 'warning', title, message, ...(opts || {}) }),
    error: (title, message, opts) => createToast({ type: 'error', title, message, ...(opts || {}) }),
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.HSStaticMethods?.autoInit) {
        window.HSStaticMethods.autoInit();
    }
});
