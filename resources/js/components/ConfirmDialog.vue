<script setup lang="ts">
import ModalDialog from '@/components/ModalDialog.vue';

/**
 * Modal confirmation of an action, built on `ModalDialog`: destructive by default (red confirm button), or
 * neutral with `tone="primary"` (e.g. granting access). "Annuler" gets the focus; nothing closes it while
 * `isProcessing`. Emits `confirm`; the parent closes it. The default slot adds content (e.g. a choice) under the message.
 */
const isOpen = defineModel<boolean>('open', { required: true });

withDefaults(
    defineProps<{
        title: string;
        message: string;
        confirmLabel: string;
        /** Label of the confirm button while `isProcessing`. */
        processingLabel?: string;
        isProcessing?: boolean;
        error?: string | null;
        tone?: 'danger' | 'primary';
    }>(),
    { tone: 'danger' },
);

const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <ModalDialog v-model:open="isOpen" :title="title" role="alertdialog" :is-dismissible="!isProcessing">
        <p>{{ message }}</p>
        <slot />
        <p v-if="error" role="alert" class="mt-3 text-red-600 dark:text-red-400">{{ error }}</p>

        <template #actions>
            <button
                type="button"
                autofocus
                :disabled="isProcessing"
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="isOpen = false"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                type="button"
                :disabled="isProcessing"
                class="w-full rounded-lg px-4 py-2.5 text-sm font-medium text-white disabled:opacity-60 sm:w-auto sm:py-2"
                :class="tone === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-600 hover:bg-brand-700'"
                @click="emit('confirm')"
            >
                {{ isProcessing && processingLabel ? processingLabel : confirmLabel }}
            </button>
        </template>
    </ModalDialog>
</template>
