<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchWordGroup, updateWordGroup } from '@/api/wordGroups';
import { fetchWords, type WordOrder } from '@/api/words';
import AddToCollectionButton from '@/components/AddToCollectionButton.vue';
import AddWordForm from '@/components/AddWordForm.vue';
import AppLayout from '@/components/AppLayout.vue';
import AccessManagement from '@/components/AccessManagement.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import DuplicateButton from '@/components/DuplicateButton.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import LeaveButton from '@/components/LeaveButton.vue';
import ShareButton from '@/components/ShareButton.vue';
import ExportPanel from '@/components/ExportPanel.vue';
import EditableNameVisibility, { type NameVisibilityPayload } from '@/components/EditableNameVisibility.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import WordListEditor from '@/components/WordListEditor.vue';
import WordListItem from '@/components/WordListItem.vue';
import WordSortControl from '@/components/WordSortControl.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import { useAuthStore } from '@/stores/auth';
import type { WordsResponse } from '@/types/api';
import type { AccessPurpose, SortDirection, Word, WordGroup, WordSort } from '@/types/models';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

/** Visitors (and unverified users, treated the same way by the API) see neither the authors nor their actions. */
const isMember = computed(() => auth.isVerified);
/** Shown to visitors but asking them to log in (sorting by author is not even shown: they do not see the authors). */
const lockedSorts = computed<WordSort[]>(() => (isMember.value ? [] : ['score']));

const wordGroupId = computed(() => String(route.params.id));
const currentPage = computed(() => {
    const page = Number(route.query.page);

    return Number.isInteger(page) && page > 0 ? page : 1;
});

/** Direction applied when choosing each criterion: alphabetical for text, most recent or best first otherwise. */
const defaultDirections: Record<WordSort, SortDirection> = {
    name: 'asc',
    author: 'asc',
    created: 'desc',
    updated: 'desc',
    score: 'desc',
};
const DEFAULT_ORDER: WordOrder = { sort: 'created', direction: 'desc' };

/** Order of the words, kept in the URL (`?sort=…&direction=…`) except the default one. */
const order = computed<WordOrder>(() => {
    const sort = route.query.sort;
    if (typeof sort !== 'string' || !(sort in defaultDirections) || (!isMember.value && (sort === 'author' || lockedSorts.value.includes(sort as WordSort)))) {
        return DEFAULT_ORDER;
    }
    const direction = route.query.direction;

    return {
        sort: sort as WordSort,
        direction: direction === 'asc' || direction === 'desc' ? direction : defaultDirections[sort as WordSort],
    };
});

/** Changing the order goes back to the first page. */
async function sortWords(sort: WordSort, direction: SortDirection): Promise<void> {
    const isDefault = sort === DEFAULT_ORDER.sort && direction === DEFAULT_ORDER.direction;
    await router.push({
        query: {
            ...route.query,
            page: undefined,
            sort: isDefault ? undefined : sort,
            direction: isDefault || direction === defaultDirections[sort] ? undefined : direction,
        },
    });
}

const wordGroup = ref<WordGroup | null>(null);
const groupStatus = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
usePageTitle(() => wordGroup.value?.name);

const words = ref<WordsResponse | null>(null);
const areWordsLoading = ref(false);
const haveWordsFailed = ref(false);

async function loadWordGroup(): Promise<void> {
    groupStatus.value = 'loading';
    try {
        wordGroup.value = await fetchWordGroup(wordGroupId.value);
        groupStatus.value = 'loaded';
    } catch (error) {
        groupStatus.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'failed';
    }
}

/** After leaving a public group, which stays visible: reloads it and its words, as the user's rights changed. */
async function onLeft(): Promise<void> {
    await Promise.all([loadWordGroup(), loadWords()]);
}

async function loadWords(): Promise<void> {
    areWordsLoading.value = true;
    haveWordsFailed.value = false;
    try {
        words.value = await fetchWords(wordGroupId.value, currentPage.value, order.value);
    } catch {
        haveWordsFailed.value = true;
    } finally {
        areWordsLoading.value = false;
    }
}

/** Reloads the words too: opening or closing contributions changes what the user can do with them. */
async function saveWordGroup(payload: NameVisibilityPayload): Promise<void> {
    wordGroup.value = await updateWordGroup(wordGroupId.value, payload);
    await loadWords();
}

const accessPurpose = computed<AccessPurpose>(() =>
    wordGroup.value && restrictsContributionsToMembers(wordGroup.value) ? 'contribution' : 'access',
);

/** A new word is listed first: show page 1 so that it is visible. */
async function onWordAdded(): Promise<void> {
    if (currentPage.value === 1) {
        await loadWords();
    } else {
        await router.push({ query: { ...route.query, page: undefined } });
    }
}

function onWordUpdated(updatedWord: Word): void {
    if (words.value) {
        words.value.data = words.value.data.map((word) => (word.id === updatedWord.id ? updatedWord : word));
    }
}

/** Edit mode: the words of the current page become text fields (sorting and pagination are hidden meanwhile). */
const isEditingWords = ref(false);
const hasEditableWords = computed(() => words.value?.data.some((word) => word.can.update) ?? false);

/** Leaves edit mode and shows the new names. */
async function onWordsSaved(): Promise<void> {
    isEditingWords.value = false;
    await loadWords();
}

/** Stays in edit mode; goes back one page when the deletion emptied the current one. */
async function onWordsDeleted(deletedCount: number): Promise<void> {
    const isPageEmptied = deletedCount === words.value?.data.length;
    if (isPageEmptied && currentPage.value > 1) {
        await router.push({ query: { ...route.query, page: String(currentPage.value - 1) } });
    } else {
        await loadWords();
    }
    if (!hasEditableWords.value) {
        isEditingWords.value = false;
    }
}

watch(wordGroupId, () => {
    isEditingWords.value = false;
});
watch(wordGroupId, loadWordGroup, { immediate: true });
watch([wordGroupId, currentPage, order], loadWords, { immediate: true });
</script>

<template>
    <AppLayout>

        <p v-if="groupStatus === 'loading'" class="mt-4 text-zinc-500 dark:text-zinc-400">{{ $t('common.loading') }}</p>

        <div v-else-if="groupStatus === 'not-found'" class="mt-4">
            <h1 class="text-2xl font-semibold">{{ $t('wordGroups.show.notFound.title') }}</h1>
            <p class="mt-2 text-zinc-500 dark:text-zinc-400">{{ $t('wordGroups.show.notFound.description') }}</p>
        </div>

        <p v-else-if="groupStatus === 'failed'" class="mt-4 text-red-600 dark:text-red-400">
            {{ $t('wordGroups.show.loadFailed') }}
            <button type="button" class="underline" @click="loadWordGroup">{{ $t('common.retry') }}</button>
        </p>

        <template v-else-if="wordGroup">
            <div class="mt-2">
                <EditableNameVisibility
                    :name="wordGroup.name"
                    :language="wordGroup.language"
                    :visibility="wordGroup.visibility"
                    :accepts-contributions="wordGroup.accepts_contributions"
                    :contributions-members-only="wordGroup.contributions_members_only"
                    kind="word-groups"
                    :creator="wordGroup.creator"
                    :can-edit="wordGroup.can.update"
                    :name-label="$t('wordGroups.fields.name')"
                    :save="saveWordGroup"
                >
                    <template v-if="wordGroup.is_favorite !== undefined" #after-title>
                        <FavoriteButton
                            v-model="wordGroup.is_favorite"
                            kind="word-groups"
                            :id="wordGroup.id"
                            :name="wordGroup.name"
                        />
                    </template>
                    <template #actions>
                        <DuplicateButton v-if="wordGroup.can.duplicate" kind="word-groups" :id="wordGroup.id" />
                        <AddToCollectionButton v-if="isMember" :word-group="wordGroup" />
                        <ShareButton
                            v-if="wordGroup.can.share"
                            kind="word-groups"
                            :id="wordGroup.id"
                            :purpose="accessPurpose"
                        />
                        <DeleteButton v-if="wordGroup.can.delete" kind="word-groups" :id="wordGroup.id" />
                        <LeaveButton
                            v-if="wordGroup.can.leave"
                            kind="word-groups"
                            :id="wordGroup.id"
                            :visibility="wordGroup.visibility"
                            @left="onLeft"
                        />
                    </template>
                </EditableNameVisibility>
            </div>

            <ExportPanel class="mt-4" kind="word-groups" :id="wordGroup.id" />
            <AccessManagement
                v-if="wordGroup.can.share"
                class="mt-8"
                kind="word-groups"
                :id="wordGroup.id"
                :purpose="accessPurpose"
            />

            <AddWordForm v-if="wordGroup.can.add_word" class="mt-6" :word-group-id="wordGroup.id" @added="onWordAdded" />
            <!-- Plain text like the other contribution messages: the header has the links to log in and register. -->
            <p v-else-if="!isMember && wordGroup.accepts_contributions" class="mt-6 text-sm text-zinc-500 dark:text-zinc-400">
                {{ auth.isAuthenticated ? $t('guest.verifyEmail') : $t('guest.contribute.wordGroup') }}
            </p>
            <p v-else-if="restrictsContributionsToMembers(wordGroup)" class="mt-6 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $t('wordGroups.show.membersOnly') }}
            </p>
            <p v-else class="mt-6 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $t('wordGroups.show.closed') }}
            </p>

            <p v-if="haveWordsFailed" class="mt-6 text-red-600 dark:text-red-400">
                {{ $t('exports.panel.loadFailed') }}
                <button type="button" class="underline" @click="loadWords">{{ $t('common.retry') }}</button>
            </p>

            <p v-else-if="words && words.meta.total === 0" class="mt-6 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-zinc-500 dark:text-zinc-400">
                {{ $t('wordGroups.show.empty') }}
            </p>

            <template v-else-if="words">
                <div class="mt-6 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                    <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('words.count', words.meta.total) }}</p>
                        <button
                            v-if="hasEditableWords && !isEditingWords"
                            type="button"
                            class="text-sm font-medium text-brand-700 dark:text-brand-300 hover:underline"
                            @click="isEditingWords = true"
                        >
                            {{ $t('wordGroups.show.editWords') }}
                        </button>
                    </div>
                    <WordSortControl
                        v-if="!isEditingWords"
                        :sort="order.sort"
                        :direction="order.direction"
                        :default-directions="defaultDirections"
                        :show-author="isMember"
                        :locked-sorts="lockedSorts"
                        @sort="sortWords"
                    />
                </div>
                <WordListEditor
                    v-if="isEditingWords"
                    class="mt-2"
                    :word-group-id="wordGroup.id"
                    :words="words.data"
                    @saved="onWordsSaved"
                    @deleted="onWordsDeleted"
                    @cancel="isEditingWords = false"
                />
                <ul
                    v-else
                    class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs"
                    :class="{ 'opacity-60': areWordsLoading }"
                >
                    <WordListItem
                        v-for="word in words.data"
                        :key="word.id"
                        :word="word"
                        @updated="onWordUpdated"
                    />
                </ul>
                <PaginationNav v-if="!isEditingWords" class="mt-4" :current-page="words.meta.current_page" :last-page="words.meta.last_page" />
            </template>
        </template>
    </AppLayout>
</template>
