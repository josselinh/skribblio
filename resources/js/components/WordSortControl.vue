<script setup lang="ts">
import { computed } from 'vue';
import type { SortDirection, WordSort } from '@/types/models';

/**
 * Order of a group's words: one pill per criterion, then a button that reverses the direction.
 * Choosing a criterion sorts in its natural direction.
 */
const props = defineProps<{
    sort: WordSort;
    direction: SortDirection;
    /** Direction applied when choosing each criterion. */
    defaultDirections: Record<WordSort, SortDirection>;
    /** Offers sorting by votes. */
    showVotes: boolean;
}>();

const emit = defineEmits<{
    sort: [sort: WordSort, direction: SortDirection];
}>();

const options = computed(() =>
    (
        [
            { value: 'created', label: 'Date d’ajout' },
            { value: 'name', label: 'Mot' },
            { value: 'score', label: 'Votes' },
            { value: 'author', label: 'Auteur' },
            { value: 'updated', label: 'Date de modification' },
        ] satisfies { value: WordSort; label: string }[]
    ).filter((option) => option.value !== 'score' || props.showVotes),
);

const directionLabels: Record<WordSort, Record<SortDirection, string>> = {
    name: { asc: 'De A à Z', desc: 'De Z à A' },
    author: { asc: 'De A à Z', desc: 'De Z à A' },
    created: { desc: 'Plus récents d’abord', asc: 'Plus anciens d’abord' },
    updated: { desc: 'Plus récents d’abord', asc: 'Plus anciens d’abord' },
    score: { desc: 'Mieux notés d’abord', asc: 'Moins bien notés d’abord' },
};

const pillClass = (isActive: boolean) =>
    [
        'cursor-pointer rounded-full border px-2.5 py-0.5 text-xs transition has-focus-visible:outline-2 has-focus-visible:outline-brand-600 dark:has-focus-visible:outline-brand-400',
        isActive ? 'border-brand-600 bg-brand-600 text-white' : 'border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800',
    ].join(' ');
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <fieldset class="flex flex-wrap items-center gap-1.5">
            <legend class="sr-only">Trier les mots par</legend>
            <span class="text-xs text-zinc-500 dark:text-zinc-400" aria-hidden="true">Tri :</span>
            <label v-for="option in options" :key="option.value" :class="pillClass(sort === option.value)">
                <input
                    type="radio"
                    name="word-sort"
                    class="sr-only"
                    :checked="sort === option.value"
                    @change="emit('sort', option.value, defaultDirections[option.value])"
                />
                {{ option.label }}
            </label>
        </fieldset>
        <button
            type="button"
            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs text-brand-700 dark:text-brand-300 hover:underline focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400"
            title="Inverser l’ordre"
            @click="emit('sort', sort, direction === 'asc' ? 'desc' : 'asc')"
        >
            <span aria-hidden="true">⇅</span>
            <span class="sr-only">Ordre :</span>
            {{ directionLabels[sort][direction] }}
            <span class="sr-only">(inverser)</span>
        </button>
    </div>
</template>
