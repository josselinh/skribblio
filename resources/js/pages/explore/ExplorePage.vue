<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter, type LocationQueryRaw } from 'vue-router';
import { fetchGroupCollectionSearchResults, fetchWordGroupSearchResults } from '@/api/search';
import AppIcon from '@/components/AppIcon.vue';
import AppLayout from '@/components/AppLayout.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import CardListSection from '@/components/CardListSection.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { usePaginatedList } from '@/composables/usePaginatedList';
import { useToasts } from '@/composables/useToasts';
import { CONTENT_LOCALES, preferredContentLanguage } from '@/lib/contentLanguage';
import { useAuthStore } from '@/stores/auth';
import type { AuthorFilter, Locale, SearchSort } from '@/types/models';

type TypeFilter = 'all' | 'word-groups' | 'group-collections';
/** Language of the lists shown, or every language. */
type LanguageFilter = Locale | 'all';

/** With "Tout", each section shows a preview; filtering on a type shows a full paginated list. */
const PREVIEW_SIZE = 6;
const PAGE_SIZE = 12;
const DEFAULT_SORT: SearchSort = 'recent';
/** Sorts shown to visitors but asking them to log in. */
const MEMBERS_ONLY_SORTS: readonly SearchSort[] = ['popular', 'largest'];

const { t } = useI18n();
const auth = useAuthStore();

/**
 * Visitors (and users whose email is not verified, whom the API treats the same way) only see public items:
 * they cannot filter by author, and the members-only sorts are shown but ask them to log in.
 */
const isMember = computed(() => auth.isVerified);
const isSortLocked = (value: SearchSort): boolean => !isMember.value && MEMBERS_ONLY_SORTS.includes(value);
const { showToast } = useToasts();
const LOCKED_OPTION_TOAST_MS = 5000;

/** A locked sort only tells the visitor to log in (the header has the links), without any request. */
function onLockedSortClick(): void {
    showToast(t('guest.lockedOption'), 'info', LOCKED_OPTION_TOAST_MS);
}

const typeOptions = computed(() => [
    { value: 'all' as const, label: t('search.types.all') },
    { value: 'word-groups' as const, label: t('search.types.wordGroups') },
    { value: 'group-collections' as const, label: t('search.types.groupCollections') },
]);
const languageOptions = computed(() => [
    ...CONTENT_LOCALES.map((value) => ({ value, label: t(`languages.names.${value}`) })),
    { value: 'all' as const, label: t('languages.filter.all') },
]);
const authorOptions = computed(() =>
    (['all', 'me', 'others'] as const).map((value) => ({ value, label: t(`search.authors.${value}`) })),
);
const sortOptions = computed(() =>
    (['recent', 'popular', 'largest', 'name'] as const).map((value) => ({ value, label: t(`search.sorts.${value}`) })),
);

/**
 * Without a search, the page is a catalogue to discover what other users share,
 * so it shows their groups and collections by default.
 */
function defaultAuthor(search: string): AuthorFilter {
    return search || !isMember.value ? 'all' : 'others';
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
/** The user's content language by default (the interface's for a visitor), left out of the URL. */
const defaultLanguage = computed<LanguageFilter>(() => preferredContentLanguage());
const language = computed(() => queryValue<LanguageFilter>('lang', [...CONTENT_LOCALES, 'all'], defaultLanguage.value));
const author = computed(() =>
    isMember.value ? queryValue<AuthorFilter>('author', ['all', 'me', 'others'], defaultAuthor(q.value)) : 'all',
);
const sort = computed(() =>
    queryValue<SearchSort>('sort', (['recent', 'popular', 'largest', 'name'] as const).filter((value) => !isSortLocked(value)), DEFAULT_SORT),
);

/** Filters set to another value than their default, counted on the "Filtres" button of phones. */
const changedFiltersCount = computed(
    () =>
        [
            type.value !== 'all',
            language.value !== defaultLanguage.value,
            author.value !== (isMember.value ? defaultAuthor(q.value) : 'all'),
            sort.value !== DEFAULT_SORT,
        ].filter(Boolean).length,
);
/** On phones, the filters are folded under a "Filtres" button so that the results come first. */
const areFiltersOpen = ref(false);

usePageTitle(() => (q.value ? t('search.resultsFor', { q: q.value }) : t('search.title')));

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
        lang: language.value,
        author: author.value,
        sort: sort.value,
        ...changes,
        page: undefined,
    };
    if (query.type === 'all') {
        query.type = undefined;
    }
    if (query.lang === defaultLanguage.value) {
        query.lang = undefined;
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
                  language: language.value === 'all' ? null : language.value,
                  sort: sort.value,
                  page: type.value === 'all' ? 1 : page,
                  perPage: type.value === 'all' ? PREVIEW_SIZE : PAGE_SIZE,
              }),
    'page',
    [q, type, language, author, sort],
);

const groupCollections = usePaginatedList(
    (page) =>
        type.value === 'word-groups'
            ? Promise.resolve(null)
            : fetchGroupCollectionSearchResults({
                  q: q.value,
                  author: author.value,
                  language: language.value === 'all' ? null : language.value,
                  sort: sort.value,
                  page: type.value === 'all' ? 1 : page,
                  perPage: type.value === 'all' ? PREVIEW_SIZE : PAGE_SIZE,
              }),
    'page',
    [q, type, language, author, sort],
);

/** In preview mode ("Tout"), link to the full list of one type when there are more results. */
function seeAll(listType: Exclude<TypeFilter, 'all'>, total: number | undefined, label: string) {
    return type.value === 'all' && total !== undefined && total > PREVIEW_SIZE
        ? { label, to: { query: { ...route.query, type: listType, page: undefined } } }
        : undefined;
}

const pillClass = (isActive: boolean) =>
    [
        'touch-target cursor-pointer rounded-full border px-3 py-1 text-sm transition',
        isActive ? 'border-brand-600 bg-brand-600 text-white' : 'border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800',
    ].join(' ');
</script>

<template>
    <AppLayout>
        <h1 class="text-2xl font-semibold">
            {{ q ? $t('search.resultsFor', { q }) : $t('search.title') }}
        </h1>
        <p v-if="isCatalogue" class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $t('search.catalogueDescription') }}
        </p>

        <form role="search" class="mt-4" @submit.prevent="updateQuery({ q: searchInput.trim() || undefined })">
            <label for="search" class="sr-only">{{ $t('layout.header.searchLabel') }}</label>
            <div class="flex gap-2">
                <div class="relative w-full">
                    <AppIcon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-zinc-400 dark:text-zinc-500" />
                    <input
                        id="search"
                        v-model="searchInput"
                        type="search"
                        :placeholder="$t('search.placeholder')"
                        class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 py-2 pr-3 pl-9 text-sm shadow-xs focus:border-brand-500 dark:focus:border-brand-400 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none"
                    />
                </div>
                <button
                    type="submit"
                    class="shrink-0 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
                >
                    {{ $t('search.submit') }}
                </button>
            </div>
        </form>

        <button
            type="button"
            class="mt-3 inline-flex items-center gap-2 rounded-full border border-zinc-300 dark:border-zinc-700 px-3 py-1.5 text-sm font-medium sm:hidden"
            :aria-expanded="areFiltersOpen"
            aria-controls="explore-filters"
            @click="areFiltersOpen = !areFiltersOpen"
        >
            {{ $t('search.filters.toggle') }}
            <span
                v-if="changedFiltersCount > 0"
                class="inline-flex min-w-5 justify-center rounded-full bg-brand-600 px-1.5 text-xs text-white"
            >
                {{ changedFiltersCount }}
            </span>
            <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="size-4 transition-transform motion-reduce:transition-none" :class="{ 'rotate-180': areFiltersOpen }">
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            id="explore-filters"
            class="mt-4 flex-col gap-3 sm:flex sm:flex-row sm:flex-wrap sm:gap-x-8"
            :class="areFiltersOpen ? 'flex' : 'hidden'"
        >
            <fieldset class="flex flex-wrap items-center gap-2 pointer-coarse:gap-y-3">
                <legend class="sr-only">{{ $t('search.filters.type') }}</legend>
                <span class="text-sm text-zinc-500 dark:text-zinc-400" aria-hidden="true">{{ $t('search.filters.typePrefix') }}</span>
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
            <fieldset class="flex flex-wrap items-center gap-2 pointer-coarse:gap-y-3">
                <legend class="sr-only">{{ $t('languages.filter.label') }}</legend>
                <span class="text-sm text-zinc-500 dark:text-zinc-400" aria-hidden="true">{{ $t('languages.filter.prefix') }}</span>
                <label v-for="option in languageOptions" :key="option.value" :class="pillClass(language === option.value)">
                    <input
                        type="radio"
                        name="lang"
                        class="sr-only"
                        :checked="language === option.value"
                        @change="updateQuery({ lang: option.value })"
                    />
                    {{ option.label }}
                </label>
            </fieldset>
            <fieldset v-if="isMember" class="flex flex-wrap items-center gap-2 pointer-coarse:gap-y-3">
                <legend class="sr-only">{{ $t('search.filters.author') }}</legend>
                <span class="text-sm text-zinc-500 dark:text-zinc-400" aria-hidden="true">{{ $t('search.filters.authorPrefix') }}</span>
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
            <fieldset class="flex flex-wrap items-center gap-2 pointer-coarse:gap-y-3">
                <legend class="sr-only">{{ $t('search.filters.sort') }}</legend>
                <span class="text-sm text-zinc-500 dark:text-zinc-400" aria-hidden="true">{{ $t('words.sort.prefix') }}</span>
                <template v-for="option in sortOptions" :key="option.value">
                    <!-- Shown to visitors too, so that they know it exists, but it asks them to log in. -->
                    <button
                        v-if="isSortLocked(option.value)"
                        type="button"
                        :class="pillClass(false)"
                        @click="onLockedSortClick"
                    >
                        {{ option.label }}
                    </button>
                    <label v-else :class="pillClass(sort === option.value)">
                        <input
                            type="radio"
                            name="sort"
                            class="sr-only"
                            :checked="sort === option.value"
                            @change="updateQuery({ sort: option.value })"
                        />
                        {{ option.label }}
                    </label>
                </template>
            </fieldset>
        </div>

        <div class="mt-8 space-y-10">
            <CardListSection
                v-if="type !== 'group-collections'"
                section-id="word-groups"
                :title="$t('search.types.wordGroups')"
                :result="wordGroups.result.value"
                :is-loading="wordGroups.isLoading.value"
                :has-failed="wordGroups.hasFailed.value"
                :empty-message="$t('wordGroups.search.empty')"
                :see-all="seeAll('word-groups', wordGroups.result.value?.meta.total, $t('search.seeAll.wordGroups', { count: wordGroups.result.value?.meta.total ?? 0 }))"
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
                :title="$t('search.types.groupCollections')"
                :result="groupCollections.result.value"
                :is-loading="groupCollections.isLoading.value"
                :has-failed="groupCollections.hasFailed.value"
                :empty-message="$t('search.emptyGroupCollections')"
                :see-all="
                    seeAll(
                        'group-collections',
                        groupCollections.result.value?.meta.total,
                        $t('search.seeAll.groupCollections', { count: groupCollections.result.value?.meta.total ?? 0 }),
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
