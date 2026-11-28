<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppIcon from '@/components/AppIcon.vue';
import { useToasts } from '@/composables/useToasts';
import type { SortDirection, WordSort } from '@/types/models';

/**
 * Order of a group's words: one pill per criterion, the active one showing its direction with an arrow.
 * Choosing a criterion sorts in its natural direction; clicking the active one again reverses it.
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

function choose(option: WordSort): void {
    emit('sort', option, option === props.sort ? (props.direction === 'asc' ? 'desc' : 'asc') : props.defaultDirections[option]);
}

const pillClass = (isActive: boolean) =>
    [
        'touch-target inline-flex cursor-pointer items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs transition focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400',
        isActive ? 'border-brand-600 bg-brand-600 text-white' : 'border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800',
    ].join(' ');
</script>

<template>
    <fieldset class="flex flex-wrap items-center gap-1.5 pointer-coarse:gap-y-3">
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
            <button
                v-else
                type="button"
                :class="pillClass(sort === option.value)"
                :aria-pressed="sort === option.value"
                :title="sort === option.value ? `${$t(`words.sort.directions.${sort}.${direction}`)} · ${$t('words.sort.reverse')}` : undefined"
                @click="choose(option.value)"
            >
                {{ option.label }}
                <template v-if="sort === option.value">
                    <AppIcon :name="direction === 'asc' ? 'arrow-up' : 'arrow-down'" class="size-3!" />
                    <span class="sr-only">{{ $t(`words.sort.directions.${sort}.${direction}`) }} {{ $t('words.sort.reverseHint') }}</span>
                </template>
            </button>
        </template>
    </fieldset>
</template>
