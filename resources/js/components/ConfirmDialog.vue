<script setup lang="ts">
import ModalDialog from '@/components/ModalDialog.vue';

/**
 * Modal confirmation of a destructive action (red confirm button), built on `ModalDialog`. "Annuler" gets the
 * focus; nothing closes it while `isProcessing`. Emits `confirm`; the parent closes it.
 */
const isOpen = defineModel<boolean>('open', { required: true });

defineProps<{
    title: string;
    message: string;
    confirmLabel: string;
    /** Label of the confirm button while `isProcessing`. */
    processingLabel?: string;
    isProcessing?: boolean;
    error?: string | null;
}>();

const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <ModalDialog v-model:open="isOpen" :title="title" role="alertdialog" :is-dismissible="!isProcessing">
        <p>{{ message }}</p>
        <p v-if="error" role="alert" class="mt-3 text-red-600">{{ error }}</p>

        <template #actions>
            <button
                type="button"
                autofocus
                :disabled="isProcessing"
                class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-900 hover:bg-zinc-50 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="isOpen = false"
            >
                Annuler
            </button>
            <button
                type="button"
                :disabled="isProcessing"
                class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="emit('confirm')"
            >
                {{ isProcessing && processingLabel ? processingLabel : confirmLabel }}
            </button>
        </template>
    </ModalDialog>
</template>
