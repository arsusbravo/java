import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export type ToastType = 'success' | 'info' | 'warning' | 'error';

export interface Toast {
    id: number;
    type: ToastType;
    message: string;
    // How long it stays, in milliseconds
    duration: number;
}

export type Flash = Partial<Record<ToastType, string | null>>;

// Errors stay longer, so there's time to read them
const DURATIONS: Record<ToastType, number> = {
    success: 4000,
    info: 5000,
    warning: 6000,
    error: 8000,
};

// Shared by the whole app, so a toast survives the page change of a redirect
const toasts = ref<Toast[]>([]);
let nextId = 1;
let listening = false;

export function toast(type: ToastType, message: string, duration?: number) {
    toasts.value.push({
        id: nextId++,
        type,
        message,
        duration: duration ?? DURATIONS[type],
    });
}

export function dismissToast(id: number) {
    toasts.value = toasts.value.filter((item) => item.id !== id);
}

function showFlash(flash?: Flash | null) {
    for (const type of ['error', 'warning', 'info', 'success'] as ToastType[]) {
        const message = flash?.[type];
        if (message) toast(type, message);
    }
}

/**
 * Turn Laravel flash messages (->with('success', ...)) into toasts: the one
 * on the first page load, then the one on every Inertia visit.
 */
export function listenForFlashMessages(initialFlash?: Flash | null) {
    showFlash(initialFlash);
    if (listening) return;
    listening = true;
    router.on('success', (event) => {
        showFlash(event.detail.page.props.flash as Flash | undefined);
    });
}

export function useToasts() {
    return { toasts, toast, dismissToast };
}
