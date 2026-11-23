import { readonly, ref } from 'vue';

/** `info` for a neutral message, e.g. an option that needs an account. */
export type ToastTone = 'success' | 'error' | 'info';

export interface Toast {
    id: number;
    message: string;
    tone: ToastTone;
}

const DEFAULT_DURATION_MS = 4000;

const toasts = ref<Toast[]>([]);
let nextId = 1;

function dismissToast(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

/** Shows a short notification at the bottom of the screen (`ToastStack`), removed after a few seconds. */
function showToast(message: string, tone: ToastTone = 'success', durationMs = DEFAULT_DURATION_MS): void {
    const id = nextId++;
    toasts.value = [...toasts.value, { id, message, tone }];
    window.setTimeout(() => dismissToast(id), durationMs);
}

/** Notifications shared by the whole application. */
export function useToasts() {
    return { toasts: readonly(toasts), showToast, dismissToast };
}
