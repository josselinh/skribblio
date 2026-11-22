<script setup lang="ts">
import { onMounted, useTemplateRef, watch } from 'vue';
import { useToasts } from '@/composables/useToasts';

/**
 * Notifications of `useToasts`, at the bottom of the screen (centered on phones, on the right from `sm`). The
 * stack is a manual popover, shown again for each new notification so that it stays above an open modal dialog.
 * Its position is set explicitly (`fixed`), so that browsers without the Popover API (e.g. Safari before iOS 17)
 * still keep it on screen instead of at the end of the page.
 */
const { toasts, dismissToast } = useToasts();

const stack = useTemplateRef<HTMLDivElement>('stack');

const supportsPopover = typeof HTMLElement !== 'undefined' && 'showPopover' in HTMLElement.prototype;

/** Opening it again puts it back at the top of the top layer, above a dialog opened since. */
function bringToFront(): void {
    const element = stack.value;
    if (!element || !supportsPopover) {
        return;
    }
    if (element.matches(':popover-open')) {
        element.hidePopover();
    }
    element.showPopover();
}

/** Open from the start, so that screen readers already know the live region when a message arrives. */
onMounted(bringToFront);

watch(
    () => toasts.value.length,
    (count, previousCount) => {
        if (count > previousCount) {
            bringToFront();
        }
    },
);

const toneClasses: Record<string, string> = {
    success: 'border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200',
    error: 'border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200',
};
</script>

<template>
    <div
        ref="stack"
        popover="manual"
        role="status"
        aria-live="polite"
        class="fixed inset-auto z-50 right-4 bottom-[max(1rem,env(safe-area-inset-bottom))] left-4 m-0 w-auto overflow-visible border-0 bg-transparent p-0 sm:left-auto sm:w-96"
    >
        <TransitionGroup
            tag="ul"
            class="flex flex-col gap-2"
            enter-from-class="translate-y-2 opacity-0"
            leave-to-class="opacity-0"
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-200"
        >
            <li
                v-for="toast in toasts"
                :key="toast.id"
                class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg"
                :class="toneClasses[toast.tone]"
            >
                <p class="flex-1">{{ toast.message }}</p>
                <button
                    type="button"
                    class="-m-1 rounded p-1 leading-none opacity-70 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400"
                    :aria-label="$t('layout.toasts.close')"
                    @click="dismissToast(toast.id)"
                >
                    <span aria-hidden="true">✕</span>
                </button>
            </li>
        </TransitionGroup>
    </div>
</template>
