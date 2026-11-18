<script setup lang="ts" generic="T extends SearchableItem">
import { ref, shallowRef, watch } from 'vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { creatorLabel } from '@/lib/creator';
import { useAuthStore } from '@/stores/auth';
import type { User, Visibility } from '@/types/models';

/** What the dropdown needs to show a result. */
export interface SearchableItem {
    id: string;
    name: string;
    visibility: Visibility;
    creator?: Pick<User, 'id' | 'name'>;
}

/**
 * Search field with a results dropdown, over items fetched by `search` as the user types.
 * Emits `select` when an item is clicked.
 */
const props = withDefaults(
    defineProps<{
        inputId: string;
        search: (query: string) => Promise<T[]>;
        placeholder: string;
        emptyMessage: string;
        /** Items that cannot be picked again (already selected or already in the collection). */
        excludedIds?: string[];
        invalid?: boolean;
        disabled?: boolean;
    }>(),
    { excludedIds: () => [], invalid: false, disabled: false },
);

const emit = defineEmits<{
    select: [item: T];
}>();

const auth = useAuthStore();

const query = ref('');
const results = shallowRef<T[]>([]);
const isOpen = ref(false);
const isSearching = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let latestSearch = 0;

/** Searches as the user types, waiting for a short pause and ignoring outdated responses. */
async function search(): Promise<void> {
    const searchId = ++latestSearch;
    isSearching.value = true;
    try {
        const items = await props.search(query.value);
        if (searchId === latestSearch) {
            results.value = items;
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

function isExcluded(item: T): boolean {
    return props.excludedIds.includes(item.id);
}

function select(item: T): void {
    emit('select', item);
    query.value = '';
}
</script>

<template>
    <div class="relative" @focusout="onFocusOut">
        <input
            :id="inputId"
            v-model="query"
            type="search"
            :placeholder="placeholder"
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
            <li v-else-if="results.length === 0" class="px-3 py-2 text-sm text-zinc-500">{{ emptyMessage }}</li>
            <li v-for="item in results" :key="item.id" role="option" :aria-selected="isExcluded(item)">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-none disabled:cursor-default disabled:opacity-50"
                    :disabled="isExcluded(item)"
                    @click="select(item)"
                >
                    <span class="truncate">{{ item.name }}</span>
                    <span class="flex shrink-0 items-center gap-2 text-xs text-zinc-500">
                        <VisibilityBadge :visibility="item.visibility" />
                        {{ creatorLabel(item.creator, auth.user?.id) }}
                    </span>
                </button>
            </li>
        </ul>
    </div>
</template>
