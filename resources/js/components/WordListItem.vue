<script setup lang="ts">
import WordInfoButton from '@/components/WordInfoButton.vue';
import WordVoteButtons from '@/components/WordVoteButtons.vue';
import type { Word } from '@/types/models';

/**
 * A word of a group, read-only: renaming and deleting happen in `WordListEditor`. Its name, then the "i" button opening who
 * added and modified it (`WordInfoButton`) and its votes.
 */
defineProps<{
    word: Word;
}>();

const emit = defineEmits<{
    updated: [word: Word];
}>();
</script>

<template>
    <li class="flex items-center justify-between gap-3 px-4 py-3 md:py-2">
        <p class="min-w-0 text-sm font-medium break-words">{{ word.name }}</p>
        <div class="flex shrink-0 items-center gap-3">
            <WordInfoButton :word="word" />
            <WordVoteButtons :word="word" @updated="emit('updated', $event)" />
        </div>
    </li>
</template>
