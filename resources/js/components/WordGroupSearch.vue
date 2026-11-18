<script setup lang="ts">
import { ref, watch } from 'vue';
import { searchWordGroups } from '@/api/wordGroups';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { useAuthStore } from '@/stores/auth';
import type { WordGroup } from '@/types/models';

/**
 * Search field over the groups the user can see (their own first, then public ones),
 * with a results dropdown. Emits `select` when a group is clicked.
 */
const props = withDefaults(
    defineProps<{
        inputId: string;
        /** Groups that cannot be picked again (already selected or already in the collection). */
        excludedIds?: string[];
        invalid?: boolean;
        disabled?: boolean;
    }>(),
    { excludedIds: () => [], invalid: false, disabled: false },
);

const emit = defineEmits<{
    select: [wordGroup: WordGroup];
}>();

const auth = useAuthStore();

const query = ref('');
const results = ref<WordGroup[]>([]);
const isOpen = ref(false);
const isSearching = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let latestSearch = 0;

/** Searches as the user types, waiting for a short pause and ignoring outdated responses. */
async function search(): Promise<void> {
    const searchId = ++latestSearch;
    isSearching.value = true;
    try {
        const groups = await searchWordGroups(query.value);
        if (searchId === latestSearch) {
            results.value = groups;
        }
    } finally {
        if (searchId === latestSearch) {
            isSearching.value = false;
        }
    }
}

watch(query, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(search, 250);
});

function open(): void {
    isOpen.value = true;
    if (results.value.length === 0) {
        void search();
    }
}

function close(): void {
    isOpen.value = false;
}

/** Closes the results when the focus leaves the search field and its results. */
function onFocusOut(event: FocusEvent): void {
    const container = event.currentTarget as HTMLElement;
    if (!container.contains(event.relatedTarget as Node | null)) {
        close();
    }
}

function isExcluded(wordGroup: WordGroup): boolean {
    return props.excludedIds.includes(wordGroup.id);
}

function select(wordGroup: WordGroup): void {
    emit('select', wordGroup);
    query.value = '';
}

function creatorLabel(wordGroup: WordGroup): string {
    return wordGroup.creator?.id === auth.user?.id ? 'vous' : (wordGroup.creator?.name ?? '');
}
</script>

<template>
    <div class="relative" @focusout="onFocusOut">
        <input
            :id="inputId"
            v-model="query"
            type="search"
            placeholder="Rechercher un groupe…"
            autocomplete="off"
            role="combobox"
            :aria-controls="`${inputId}-results`"
            :aria-expanded="isOpen"
            :aria-invalid="invalid ? true : undefined"
            :disabled="disabled"
            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-none disabled:opacity-60 aria-invalid:border-red-500"
            @focus="open"
            @keydown.esc="close"
        />
        <ul
            v-if="isOpen"
            :id="`${inputId}-results`"
            role="listbox"
            class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg"
        >
            <li v-if="isSearching && results.length === 0" class="px-3 py-2 text-sm text-zinc-500">Recherche…</li>
            <li v-else-if="results.length === 0" class="px-3 py-2 text-sm text-zinc-500">Aucun groupe trouvé.</li>
            <li v-for="wordGroup in results" :key="wordGroup.id" role="option" :aria-selected="isExcluded(wordGroup)">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-none disabled:cursor-default disabled:opacity-50"
                    :disabled="isExcluded(wordGroup)"
                    @click="select(wordGroup)"
                >
                    <span class="truncate">{{ wordGroup.name }}</span>
                    <span class="flex shrink-0 items-center gap-2 text-xs text-zinc-500">
                        <VisibilityBadge :visibility="wordGroup.visibility" />
                        {{ creatorLabel(wordGroup) }}
                    </span>
                </button>
            </li>
        </ul>
    </div>
</template>
