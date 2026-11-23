<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { voteForWord } from '@/api/words';
import type { Word, WordVote } from '@/types/models';

/**
 * Thumbs up and down of a word with their counts. When the user may vote, clicking a thumb records
 * the vote, clicking it again removes it; the change is shown at once and reverted if the request fails.
 * Otherwise (group closed to contributions, author of the word, visitor) the same thumbs are only displayed,
 * the user's own vote still highlighted.
 */
const props = defineProps<{
    word: Word;
}>();

const emit = defineEmits<{
    updated: [word: Word];
}>();

const { t } = useI18n();

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
    { vote: 'up', emoji: '👍', label: t('words.votes.up'), activeClass: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 ring-emerald-300 dark:ring-emerald-800' },
    { vote: 'down', emoji: '👎', label: t('words.votes.down'), activeClass: 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400 ring-red-300 dark:ring-red-800' },
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
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs tabular-nums ring-1 transition focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400"
                :class="word.my_vote === button.vote ? button.activeClass : 'text-zinc-600 dark:text-zinc-400 ring-zinc-200 dark:ring-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                :aria-pressed="word.my_vote === button.vote"
                :aria-label="$t('words.votes.buttonLabel', { vote: button.label, word: word.name, count: countOf(button.vote) })"
                :title="hasFailed ? $t('words.votes.failed') : button.label"
                @click="toggle(button.vote)"
            >
                <span aria-hidden="true">{{ button.emoji }}</span>
                {{ countOf(button.vote) }}
            </button>
            <span
                v-else
                class="inline-flex cursor-default items-center gap-1 rounded-full px-2 py-0.5 text-xs tabular-nums ring-1"
                :class="word.my_vote === button.vote ? button.activeClass : 'text-zinc-500 dark:text-zinc-400 ring-zinc-200 dark:ring-zinc-700'"
                :title="button.label"
            >
                <span aria-hidden="true">{{ button.emoji }}</span>
                <span class="sr-only">{{ $t('words.votes.countLabel', { vote: button.label }) }}</span>
                {{ countOf(button.vote) }}
            </span>
        </template>
    </div>
</template>
