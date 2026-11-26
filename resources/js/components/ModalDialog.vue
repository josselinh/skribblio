<script setup lang="ts">
import { onBeforeUnmount, useId, useTemplateRef, watch } from 'vue';

/**
 * Modal built on the native <dialog> (focus trap, Esc, backdrop): a bottom sheet on phones, a centered card from
 * the `sm` breakpoint. The default slot holds the content, the `actions` slot the buttons (stacked full width on
 * phones, the first one at the bottom). Esc and a click on the backdrop close it unless `isDismissible` is false.
 * Teleported to <body>, so that it still shows when the button opening it sits in a menu that closed meanwhile
 * (`ActionsMenu`).
 */
const isOpen = defineModel<boolean>('open', { required: true });

const props = withDefaults(
    defineProps<{
        title: string;
        role?: 'dialog' | 'alertdialog';
        isDismissible?: boolean;
    }>(),
    { role: 'dialog', isDismissible: true },
);

const dialog = useTemplateRef<HTMLDialogElement>('dialog');
const titleId = useId();

/** The page behind must not scroll while the dialog is open (mostly visible on phones). */
function lockPageScroll(isLocked: boolean): void {
    document.documentElement.style.overflow = isLocked ? 'hidden' : '';
}

watch(
    [isOpen, dialog],
    ([open, element]) => {
        if (!element) {
            return;
        }
        if (open && !element.open) {
            element.showModal();
        } else if (!open && element.open) {
            element.close();
        }
        lockPageScroll(open);
    },
    { immediate: true },
);

onBeforeUnmount(() => lockPageScroll(false));

function close(): void {
    if (props.isDismissible) {
        isOpen.value = false;
    }
}

/** Esc closes the dialog natively: keep the model in sync, or prevent it when not dismissible. */
function onCancel(event: Event): void {
    event.preventDefault();
    close();
}

/** A click on the backdrop targets the dialog itself, not its content. */
function onClick(event: MouseEvent): void {
    if (event.target === dialog.value) {
        close();
    }
}
</script>

<template>
    <Teleport to="body">
    <dialog
        ref="dialog"
        :role="role"
        :aria-labelledby="titleId"
        class="mx-0 mt-auto mb-0 max-h-[90dvh] w-full max-w-none overflow-y-auto rounded-t-2xl bg-white dark:bg-zinc-900 p-0 text-left font-normal whitespace-normal text-zinc-900 dark:text-zinc-100 shadow-xl backdrop:bg-zinc-950/40 dark:backdrop:bg-black/60 sm:m-auto sm:max-w-md sm:rounded-2xl"
        @cancel="onCancel"
        @click="onClick"
    >
        <div class="px-5 pt-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] sm:p-6">
            <h2 :id="titleId" class="text-lg font-semibold">{{ title }}</h2>
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                <slot />
            </div>
            <div v-if="$slots.actions" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <slot name="actions" />
            </div>
        </div>
    </dialog>
    </Teleport>
</template>
