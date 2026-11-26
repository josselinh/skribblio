<script setup lang="ts">
import { Comment, Fragment, ref, useId, useTemplateRef, type VNode } from 'vue';

/**
 * "⋮" button opening the menu of the actions of a group or collection page (in the corner of its title, the menu opening
 * leftward) (the default slot, buttons styled with
 * `actionButtonClass` from `@/lib/actionsMenu`). The menu is only hidden when closed, not unmounted, so that the dialogs
 * its buttons open (teleported to <body> by `ModalDialog`) stay alive. Nothing is rendered when the slot holds no button.
 */
const isOpen = ref(false);
const trigger = useTemplateRef<HTMLButtonElement>('trigger');
const panelId = useId();
function toggle(): void {
    isOpen.value = !isOpen.value;
}

/** Whether the slot renders something, its buttons being often all hidden by their `v-if`. */
function hasContent(nodes: VNode[] | undefined): boolean {
    return (nodes ?? []).some((node) => {
        if (node.type === Comment) {
            return false;
        }

        return node.type === Fragment ? hasContent(node.children as VNode[]) : true;
    });
}

function close(): void {
    isOpen.value = false;
}

/** Closes the menu when the focus leaves it (click elsewhere, Tab). */
function onFocusOut(event: FocusEvent): void {
    const container = event.currentTarget as HTMLElement;
    if (!container.contains(event.relatedTarget as Node | null)) {
        close();
    }
}

/**
 * Choosing an entry closes the menu and gives the focus back to "⋮" before the entry's dialog opens, so that
 * the dialog returns the focus there once closed.
 */
function onItemClick(event: MouseEvent): void {
    if (isOpen.value && (event.target as HTMLElement).closest('button')) {
        close();
        trigger.value?.focus();
    }
}

function onEscape(): void {
    if (isOpen.value) {
        close();
        trigger.value?.focus();
    }
}
</script>

<template>
    <div v-if="hasContent($slots.default?.())" class="relative" @focusout="onFocusOut" @keydown.esc="onEscape">
        <button
            ref="trigger"
            type="button"
            class="rounded-md p-1 text-lg leading-none text-zinc-400 dark:text-zinc-500 transition hover:bg-zinc-200/70 dark:hover:bg-zinc-700/60 hover:text-zinc-700 dark:hover:text-zinc-300 focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400"
            :class="{ 'bg-zinc-200/70 dark:bg-zinc-700/60 text-zinc-700 dark:text-zinc-300': isOpen }"
            :aria-expanded="isOpen"
            :aria-controls="panelId"
            :aria-label="$t('common.actions')"
            :title="$t('common.actions')"
            @click="toggle"
        >
            <span aria-hidden="true">⋮</span>
        </button>

        <div
            v-show="isOpen"
            :id="panelId"
            class="absolute top-full right-0 z-20 mt-1 w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 py-1 shadow-lg"
            @click="onItemClick"
        >
            <slot />
        </div>
    </div>
</template>
