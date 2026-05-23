import { readonly, reactive } from 'vue';

let toastId = 0;

const toasts = reactive([]);

const confirmDialog = reactive({
    open: false,
    title: 'Konfirmasi',
    message: 'Lanjutkan tindakan ini?',
    confirmText: 'Lanjutkan',
    cancelText: 'Batal',
    tone: 'danger',
    resolve: null,
});

function normalizeToast(options) {
    if (typeof options === 'string') {
        return { message: options };
    }

    return options || {};
}

export function showToast(options) {
    const toast = normalizeToast(options);
    const id = ++toastId;
    const duration = toast.duration ?? 4000;

    toasts.push({
        id,
        tone: toast.tone || 'success',
        title: toast.title || '',
        message: toast.message || '',
    });

    if (duration > 0) {
        window.setTimeout(() => dismissToast(id), duration);
    }

    return id;
}

export function dismissToast(id) {
    const index = toasts.findIndex((toast) => toast.id === id);

    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

export function askConfirmation(options = {}) {
    if (confirmDialog.resolve) {
        confirmDialog.resolve(false);
    }

    Object.assign(confirmDialog, {
        open: true,
        title: options.title || 'Konfirmasi',
        message: options.message || 'Lanjutkan tindakan ini?',
        confirmText: options.confirmText || 'Lanjutkan',
        cancelText: options.cancelText || 'Batal',
        tone: options.tone || 'danger',
        resolve: null,
    });

    return new Promise((resolve) => {
        confirmDialog.resolve = resolve;
    });
}

export function resolveConfirmation(confirmed) {
    const resolve = confirmDialog.resolve;

    confirmDialog.open = false;
    confirmDialog.resolve = null;

    if (resolve) {
        resolve(confirmed);
    }
}

export function useToast() {
    return {
        toasts: readonly(toasts),
        showToast,
        dismissToast,
    };
}

export function useConfirm() {
    return {
        confirmDialog: readonly(confirmDialog),
        confirm: askConfirmation,
        cancelConfirm: () => resolveConfirmation(false),
        acceptConfirm: () => resolveConfirmation(true),
    };
}
