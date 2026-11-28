<script setup lang="ts">
import WordMeta from '@/components/WordMeta.vue';
import WordVoteButtons from '@/components/WordVoteButtons.vue';
import type { Word } from '@/types/models';

/**
 * A word of a group, read-only: renaming and deleting happen in `WordListEditor`. On phones, the name and its votes,
 * then the author and the dates on a second line; from `md`, everything on one line.
 */
defineProps<{
    word: Word;
}>();

const emit = defineEmits<{
    updated: [word: Word];
}>();
</script>

<template>
    <li class="flex items-start justify-between gap-3 px-4 py-3 md:items-center md:py-2">
        <div class="min-w-0 md:flex md:flex-1 md:items-baseline md:gap-4">
            <p class="text-sm font-medium break-words md:w-56 md:shrink-0">{{ word.name }}</p>
            <WordMeta class="mt-0.5 md:mt-0" :word="word" />
        </div>
        <WordVoteButtons class="shrink-0" :word="word" @updated="emit('updated', $event)" />
    </li>
</template>
