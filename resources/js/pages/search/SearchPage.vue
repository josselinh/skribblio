<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter, type LocationQueryRaw } from 'vue-router';
import { fetchGroupCollectionSearchResults, fetchWordGroupSearchResults } from '@/api/search';
import AppLayout from '@/components/AppLayout.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import CardListSection from '@/components/CardListSection.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePaginatedList } from '@/composables/usePaginatedList';
import type { AuthorFilter, SearchSort } from '@/types/models';

type TypeFilter = 'all' | 'word-groups' | 'group-collections';

/** With "Tout", each section shows a preview; filtering on a type shows a full paginated list. */
const PREVIEW_SIZE = 6;
const PAGE_SIZE = 12;
const DEFAULT_SORT: SearchSort = 'recent';

const typeOptions: { value: TypeFilter; label: string }[] = [
    { value: 'all', label: 'Tout' },
    { value: 'word-groups', label: 'Groupes' },
    { value: 'group-collections', label: 'Collections' },
];
const authorOptions: { value: AuthorFilter; label: string }[] = [
    { value: 'all', label: 'Tout le monde' },
    { value: 'me', label: 'Moi' },
    { value: 'others', label: 'Les autres' },
];
const sortOptions: { value: SearchSort; label: string }[] = [
    { value: 'recent', label: 'Récents' },
    { value: 'popular', label: 'Populaires' },
    { value: 'largest', label: 'Les plus fournis' },
    { value: 'name', label: 'A → Z' },
];

/**
 * Without a search, the page is a catalogue to discover what other users share,
 * so it shows their groups and collections by default.
 */
function defaultAuthor(search: string): AuthorFilter {
    return search ? 'all' : 'others';
}

const route = useRoute();
const router = useRouter();

function queryValue<T extends string>(key: string, allowed: readonly T[], fallback: T): T {
    const value = route.query[key];

    return typeof value === 'string' && (allowed as readonly string[]).includes(value) ? (value as T) : fallback;
}

const q = computed(() => (typeof route.query.q === 'string' ? route.query.q : ''));
const type = computed(() => queryValue<TypeFilter>('type', ['all', 'word-groups', 'group-collections'], 'all'));
const isCatalogue = computed(() => q.value === '');
const author = computed(() => queryValue<AuthorFilter>('author', ['all', 'me', 'others'], defaultAuthor(q.value)));
const sort = computed(() => queryValue<SearchSort>('sort', ['recent', 'popular', 'largest', 'name'], DEFAULT_SORT));

const searchInput = ref(q.value);
watch(q, (value) => (searchInput.value = value));

/**
 * Updates the filters in the URL, which reloads the results; always goes back to the first page.
 * Default values are left out of the URL (the default author depends on whether there is a search).
 */
function updateQuery(changes: LocationQueryRaw): Promise<unknown> {
    const query: LocationQueryRaw = {
        q: q.value || undefined,
        type: type.value,
        author: author.value,
        sort: sort.value,
        ...changes,
        page: undefined,
    };
    if (query.type === 'all') {
        query.type = undefined;
    }
    if (query.author === defaultAuthor(typeof query.q === 'string' ? query.q : '')) {
        query.author = undefined;
    }
    if (query.sort === DEFAULT_SORT) {
        query.sort = undefined;
    }

    return router.push({ query });
}

const wordGroups = usePaginatedList(
    (page) =>
        type.value === 'group-collections'
            ? Promise.resolve(null)
            : fetchWordGroupSearchResults({
                  q: q.value,
                  author: author.value,
                  sort: sort.value,
                  page: type.value === 'all' ? 1 : page,
                  perPage: type.value === 'all' ? PREVIEW_SIZE : PAGE_SIZE,
              }),
    'page',
    [q, type, author, sort],
);

const groupCollections = usePaginatedList(
    (page) =>
        type.value === 'word-groups'
            ? Promise.resolve(null)
            : fetchGroupCollectionSearchResults({
                  q: q.value,
                  author: author.value,
                  sort: sort.value,
                  page: type.value === 'all' ? 1 : page,
                  perPage: type.value === 'all' ? PREVIEW_SIZE : PAGE_SIZE,
              }),
    'page',
    [q, type, author, sort],
);

/** In preview mode ("Tout"), link to the full list of one type when there are more results. */
function seeAll(listType: Exclude<TypeFilter, 'all'>, total: number | undefined, label: string) {
    return type.value === 'all' && total !== undefined && total > PREVIEW_SIZE
        ? { label, to: { query: { ...route.query, type: listType, page: undefined } } }
        : undefined;
}

const pillClass = (isActive: boolean) =>
    [
        'cursor-pointer rounded-full border px-3 py-1 text-sm transition',
        isActive ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-300 bg-white hover:bg-zinc-50',
    ].join(' ');
</script>

<template>
    <AppLayout>
        <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
            ← Retour au tableau de bord
        </RouterLink>

        <h1 class="mt-2 text-2xl font-semibold">
            <template v-if="q">Résultats pour « {{ q }} »</template>
            <template v-else>Explorer</template>
        </h1>
        <p v-if="isCatalogue" class="mt-1 text-sm text-zinc-500">
            Découvrez les groupes et collections partagés par les autres utilisateurs, ou cherchez-en un par son nom.
        </p>

        <form role="search" class="mt-4" @submit.prevent="updateQuery({ q: searchInput.trim() || undefined })">
            <label for="search" class="sr-only">Rechercher un groupe ou une collection</label>
            <div class="flex gap-2">
                <input
                    id="search"
                    v-model="searchInput"
                    type="search"
                    placeholder="Nom d'un groupe ou d'une collection…"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-none"
                />
                <button
                    type="submit"
                    class="shrink-0 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700"
                >
                    Rechercher
                </button>
            </div>
        </form>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:gap-x-8">
            <fieldset class="flex flex-wrap items-center gap-2">
                <legend class="sr-only">Type</legend>
                <span class="text-sm text-zinc-500" aria-hidden="true">Type :</span>
                <label v-for="option in typeOptions" :key="option.value" :class="pillClass(type === option.value)">
                    <input
                        type="radio"
                        name="type"
                        class="sr-only"
                        :checked="type === option.value"
                        @change="updateQuery({ type: option.value })"
                    />
                    {{ option.label }}
                </label>
            </fieldset>
            <fieldset class="flex flex-wrap items-center gap-2">
                <legend class="sr-only">Créés par</legend>
                <span class="text-sm text-zinc-500" aria-hidden="true">Créés par :</span>
                <label v-for="option in authorOptions" :key="option.value" :class="pillClass(author === option.value)">
                    <input
                        type="radio"
                        name="author"
                        class="sr-only"
                        :checked="author === option.value"
                        @change="updateQuery({ author: option.value })"
                    />
                    {{ option.label }}
                </label>
            </fieldset>
            <fieldset class="flex flex-wrap items-center gap-2">
                <legend class="sr-only">Trier par</legend>
                <span class="text-sm text-zinc-500" aria-hidden="true">Tri :</span>
                <label v-for="option in sortOptions" :key="option.value" :class="pillClass(sort === option.value)">
                    <input
                        type="radio"
                        name="sort"
                        class="sr-only"
                        :checked="sort === option.value"
                        @change="updateQuery({ sort: option.value })"
                    />
                    {{ option.label }}
                </label>
            </fieldset>
        </div>

        <div class="mt-8 space-y-10">
            <CardListSection
                v-if="type !== 'group-collections'"
                section-id="word-groups"
                title="Groupes"
                :result="wordGroups.result.value"
                :is-loading="wordGroups.isLoading.value"
                :has-failed="wordGroups.hasFailed.value"
                empty-message="Aucun groupe trouvé."
                :see-all="seeAll('word-groups', wordGroups.result.value?.meta.total, `Voir les ${wordGroups.result.value?.meta.total} groupes →`)"
                :paginated="type !== 'all'"
                @retry="wordGroups.reload"
            >
                <template #item="{ item }">
                    <WordGroupCard :word-group="item" />
                </template>
            </CardListSection>

            <CardListSection
                v-if="type !== 'word-groups'"
                section-id="group-collections"
                title="Collections"
                :result="groupCollections.result.value"
                :is-loading="groupCollections.isLoading.value"
                :has-failed="groupCollections.hasFailed.value"
                empty-message="Aucune collection trouvée."
                :see-all="
                    seeAll(
                        'group-collections',
                        groupCollections.result.value?.meta.total,
                        `Voir les ${groupCollections.result.value?.meta.total} collections →`,
                    )
                "
                :paginated="type !== 'all'"
                @retry="groupCollections.reload"
            >
                <template #item="{ item }">
                    <GroupCollectionCard :group-collection="item" />
                </template>
            </CardListSection>
        </div>
    </AppLayout>
</template>
