<script setup lang="ts">
import { computed, useId } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToasts } from '@/composables/useToasts';
import type { SortDirection, WordSort } from '@/types/models';

/**
 * Order of a group's words: one pill per criterion (a drop-down list on phones, so that it fits on one line), then
 * a button that reverses the direction.
 * Choosing a criterion sorts in its natural direction.
 * A locked criterion (for visitors) stays visible but only shows a notification asking to log in.
 */
const props = defineProps<{
    sort: WordSort;
    direction: SortDirection;
    /** Direction applied when choosing each criterion. */
    defaultDirections: Record<WordSort, SortDirection>;
    /** Offers sorting by author (not to visitors, who do not see the authors). */
    showAuthor: boolean;
    /** Criteria shown but asking to log in. */
    lockedSorts: WordSort[];
}>();

const emit = defineEmits<{
    sort: [sort: WordSort, direction: SortDirection];
}>();

const { t } = useI18n();

const sorts: WordSort[] = ['created', 'name', 'score', 'author', 'updated'];

const options = computed(() =>
    sorts
        .filter((sort) => sort !== 'author' || props.showAuthor)
        .map((sort) => ({ value: sort, label: t(`words.sort.criteria.${sort}`) })),
);

const { showToast } = useToasts();
const LOCKED_OPTION_TOAST_MS = 5000;

/** The header has the links to log in and register: the notification only says why nothing happens. */
function onLockedSortClick(): void {
    showToast(t('guest.lockedOption'), 'info', LOCKED_OPTION_TOAST_MS);
}

const selectId = useId();

/** A locked criterion chosen in the drop-down list goes back to the current one. */
function onSelect(event: Event): void {
    const select = event.target as HTMLSelectElement;
    const value = select.value as WordSort;
    if (props.lockedSorts.includes(value)) {
        select.value = props.sort;
        onLockedSortClick();
        return;
    }
    emit('sort', value, props.defaultDirections[value]);
}


const pillClass = (isActive: boolean) =>
    [
        'cursor-pointer rounded-full border px-2.5 py-0.5 text-xs transition has-focus-visible:outline-2 has-focus-visible:outline-brand-600 dark:has-focus-visible:outline-brand-400',
        isActive ? 'border-brand-600 bg-brand-600 text-white' : 'border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800',
    ].join(' ');
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1.5 sm:hidden">
            <label :for="selectId" class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('words.sort.prefix') }}</label>
            <select
                :id="selectId"
                :value="sort"
                class="rounded-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 py-1.5 pr-6 pl-2.5 text-xs"
                @input="onSelect"
            >
                <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
        </div>
        <fieldset class="hidden flex-wrap items-center gap-1.5 sm:flex">
            <legend class="sr-only">{{ $t('words.sort.legend') }}</legend>
            <span class="text-xs text-zinc-500 dark:text-zinc-400" aria-hidden="true">{{ $t('words.sort.prefix') }}</span>
            <template v-for="option in options" :key="option.value">
                <button
                    v-if="lockedSorts.includes(option.value)"
                    type="button"
                    :class="pillClass(false)"
                    @click="onLockedSortClick"
                >
                    {{ option.label }}
                </button>
                <label v-else :class="pillClass(sort === option.value)">
                    <input
                        type="radio"
                        name="word-sort"
                        class="sr-only"
                        :checked="sort === option.value"
                        @change="emit('sort', option.value, defaultDirections[option.value])"
                    />
                    {{ option.label }}
                </label>
            </template>
        </fieldset>
        <button
            type="button"
            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs text-brand-700 dark:text-brand-300 hover:underline focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400"
            :title="$t('words.sort.reverse')"
            @click="emit('sort', sort, direction === 'asc' ? 'desc' : 'asc')"
        >
            <span aria-hidden="true">⇅</span>
            <span class="sr-only">{{ $t('words.sort.order') }}</span>
            {{ $t(`words.sort.directions.${sort}.${direction}`) }}
            <span class="sr-only">{{ $t('words.sort.reverseHint') }}</span>
        </button>
    </div>
</template>
