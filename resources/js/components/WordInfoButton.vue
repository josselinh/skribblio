<script setup lang="ts">
import { ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import WordMeta from '@/components/WordMeta.vue';
import type { Word } from '@/types/models';

/**
 * Small "i" button before the votes of a word: it opens a dialog telling who added the word and who modified it, and when
 * (`WordMeta`).
 */
defineProps<{
    word: Word;
}>();

const isOpen = ref(false);
</script>

<template>
    <button
        type="button"
        class="touch-target inline-flex shrink-0 items-center justify-center rounded-full p-0.5 text-zinc-400 hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-200"
        aria-haspopup="dialog"
        :aria-label="$t('words.info.label', { name: word.name })"
        @click="isOpen = true"
    >
        <AppIcon name="info" />
    </button>

    <ModalDialog v-model:open="isOpen" :title="word.name">
        <WordMeta :word="word" />

        <template #actions>
            <button
                type="button"
                autofocus
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 sm:w-auto sm:py-2"
                @click="isOpen = false"
            >
                {{ $t('common.close') }}
            </button>
        </template>
    </ModalDialog>
</template>
