<script setup lang="ts">
import { ref } from 'vue';
import { voteForWord } from '@/api/words';
import type { Word, WordVote } from '@/types/models';

/**
 * Thumbs up and down of a word with their counts. When the user may vote, clicking a thumb records
 * the vote, clicking it again removes it; the change is shown at once and reverted if the request fails.
 * Otherwise the counts are only displayed.
 */
const props = defineProps<{
    word: Word;
}>();

const emit = defineEmits<{
    updated: [word: Word];
}>();

const isSaving = ref(false);
const hasFailed = ref(false);

/** The word as it will be once the vote is recorded. */
function withVote(word: Word, vote: WordVote | null): Word {
    const count = (type: WordVote, current: number): number =>
        current - (word.my_vote === type ? 1 : 0) + (vote === type ? 1 : 0);

    return {
        ...word,
        upvotes_count: count('up', word.upvotes_count),
        downvotes_count: count('down', word.downvotes_count),
        my_vote: vote,
    };
}

async function toggle(vote: WordVote): Promise<void> {
    if (isSaving.value || !props.word.can.vote) {
        return;
    }
    const previousWord = props.word;
    const newVote = previousWord.my_vote === vote ? null : vote;
    emit('updated', withVote(previousWord, newVote));
    isSaving.value = true;
    hasFailed.value = false;
    try {
        emit('updated', await voteForWord(previousWord.id, newVote));
    } catch {
        emit('updated', previousWord);
        hasFailed.value = true;
    } finally {
        isSaving.value = false;
    }
}

const buttons: { vote: WordVote; emoji: string; label: string; activeClass: string }[] = [
    { vote: 'up', emoji: '👍', label: 'J’aime', activeClass: 'bg-blue-50 text-blue-700 ring-blue-300' },
    { vote: 'down', emoji: '👎', label: 'Je n’aime pas', activeClass: 'bg-red-50 text-red-700 ring-red-300' },
];

function countOf(vote: WordVote): number {
    return vote === 'up' ? props.word.upvotes_count : props.word.downvotes_count;
}
</script>

<template>
    <div class="flex items-center gap-1.5 whitespace-nowrap">
        <template v-for="button in buttons" :key="button.vote">
            <button
                v-if="word.can.vote"
                type="button"
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs tabular-nums ring-1 transition focus-visible:outline-2 focus-visible:outline-zinc-900"
                :class="word.my_vote === button.vote ? button.activeClass : 'text-zinc-600 ring-zinc-200 hover:bg-zinc-100'"
                :aria-pressed="word.my_vote === button.vote"
                :aria-label="`${button.label} « ${word.name} » (${countOf(button.vote)})`"
                :title="hasFailed ? 'Impossible d’enregistrer le vote, veuillez réessayer.' : button.label"
                @click="toggle(button.vote)"
            >
                <span aria-hidden="true">{{ button.emoji }}</span>
                {{ countOf(button.vote) }}
            </button>
            <span
                v-else
                class="inline-flex items-center gap-1 px-2 py-0.5 text-xs text-zinc-500 tabular-nums"
                :title="button.label"
            >
                <span aria-hidden="true">{{ button.emoji }}</span>
                <span class="sr-only">{{ button.label }} :</span>
                {{ countOf(button.vote) }}
            </span>
        </template>
    </div>
</template>
