<script setup lang="ts">
import UserLink from '@/components/UserLink.vue';
import WordVoteButtons from '@/components/WordVoteButtons.vue';
import { formatDateTime } from '@/lib/format';
import type { Word } from '@/types/models';

/** A word of a group, read-only: renaming and deleting happen in `WordListEditor`. */
defineProps<{
    word: Word;
    /** Shows the votes. */
    showVotes: boolean;
}>();

const emit = defineEmits<{
    updated: [word: Word];
}>();
</script>

<template>
    <li class="px-4 py-3">
        <div class="flex items-start justify-between gap-3">
            <p class="min-w-0 text-sm font-medium break-words">{{ word.name }}</p>
            <WordVoteButtons v-if="showVotes" class="shrink-0" :word="word" @updated="emit('updated', $event)" />
        </div>
        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            <template v-if="word.creator">par <UserLink :user="word.creator" self-label="vous" /></template>
            <span v-else class="italic">Compte supprimé</span>
            <template v-if="word.created_at">
                · <time :datetime="word.created_at">{{ formatDateTime(word.created_at) }}</time>
            </template>
            <template v-if="word.updated_at && word.updated_at !== word.created_at">
                · modifié le <time :datetime="word.updated_at">{{ formatDateTime(word.updated_at) }}</time>
            </template>
        </p>
    </li>
</template>
