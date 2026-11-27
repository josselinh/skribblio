<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { fetchWordGroup } from '@/api/wordGroups';
import { fetchWords } from '@/api/words';
import AddToCollectionButton from '@/components/AddToCollectionButton.vue';
import AppLayout from '@/components/AppLayout.vue';
import DuplicateButton from '@/components/DuplicateButton.vue';
import ExportPanel from '@/components/ExportPanel.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemHeader from '@/components/ItemHeader.vue';
import LeaveButton from '@/components/LeaveButton.vue';
import PendingAccessRequests from '@/components/PendingAccessRequests.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import WordListItem from '@/components/WordListItem.vue';
import WordSortControl from '@/components/WordSortControl.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { useWordOrder, WORD_SORT_DEFAULT_DIRECTIONS } from '@/composables/useWordOrder';
import { secondaryButtonClass } from '@/lib/buttons';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import { listPanelClass, listPanelHeaderClass } from '@/lib/sections';
import { useAuthStore } from '@/stores/auth';
import type { WordsResponse } from '@/types/api';
import type { Word, WordGroup, WordSort } from '@/types/models';

/**
 * Page of a group, made for reading: its title, the pending access requests (creator only), the export, the actions that do not change it (duplicate, add to a
 * collection, leave) and its words with their votes. Changing it happens on its edit page (`EditWordGroupPage`),
 * reached through "Gérer" (creator) or "Contribuer" (contributors).
 */
const route = useRoute();
const auth = useAuthStore();

/** Visitors (and unverified users, treated the same way by the API) see neither the authors nor their actions. */
const isMember = computed(() => auth.isVerified);
/** Shown to visitors but asking them to log in (sorting by author is not even shown: they do not see the authors). */
const lockedSorts = computed<WordSort[]>(() => (isMember.value ? [] : ['score']));
const { order, sortWords } = useWordOrder(() => (isMember.value ? [] : ['author', ...lockedSorts.value]));

const wordGroupId = computed(() => String(route.params.id));
const currentPage = computed(() => {
    const page = Number(route.query.page);

    return Number.isInteger(page) && page > 0 ? page : 1;
});

const wordGroup = ref<WordGroup | null>(null);
const groupStatus = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
usePageTitle(() => wordGroup.value?.name);

/** Whether the user can change something: everything for the creator, the words for a contributor. */
const canEdit = computed(() => wordGroup.value !== null && (wordGroup.value.can.update || wordGroup.value.can.add_word));

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

function onWordUpdated(updatedWord: Word): void {
    if (words.value) {
        words.value.data = words.value.data.map((word) => (word.id === updatedWord.id ? updatedWord : word));
    }
}

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
            <ItemHeader
                class="mt-2"
                :name="wordGroup.name"
                :language="wordGroup.language"
                :visibility="wordGroup.visibility"
                :accepts-contributions="wordGroup.accepts_contributions"
                :contributions-members-only="wordGroup.contributions_members_only"
                kind="word-groups"
                :creator="wordGroup.creator"
                :updated-at="wordGroup.updated_at"
            >
                <template v-if="wordGroup.is_favorite !== undefined" #after-title>
                    <FavoriteButton
                        v-model="wordGroup.is_favorite"
                        kind="word-groups"
                        :id="wordGroup.id"
                        :name="wordGroup.name"
                    />
                </template>
            </ItemHeader>

            <PendingAccessRequests v-if="wordGroup.can.manage_access" class="mt-4" kind="word-groups" :id="wordGroup.id" />

            <ExportPanel class="mt-4" kind="word-groups" :id="wordGroup.id">
                <template #actions>
                    <RouterLink
                        v-if="canEdit"
                        :to="{ name: 'word-groups.edit', params: { id: wordGroup.id } }"
                        :class="secondaryButtonClass"
                    >
                        {{ wordGroup.can.update ? $t('wordGroups.show.manage') : $t('wordGroups.show.contribute') }}
                    </RouterLink>
                    <DuplicateButton v-if="wordGroup.can.duplicate" kind="word-groups" :id="wordGroup.id" />
                    <AddToCollectionButton v-if="isMember" :word-group="wordGroup" />
                    <LeaveButton
                        v-if="wordGroup.can.leave"
                        kind="word-groups"
                        :id="wordGroup.id"
                        :visibility="wordGroup.visibility"
                       
                        @left="onLeft"
                    />
                </template>
            </ExportPanel>

            <section class="mt-8" :class="listPanelClass" aria-labelledby="group-words-title">
                <div :class="listPanelHeaderClass">
                    <h2 id="group-words-title" class="text-lg font-semibold">
                        {{ $t('common.words') }}
                        <span v-if="words" class="font-normal text-zinc-500 dark:text-zinc-400">({{ words.meta.total }})</span>
                    </h2>
                    <WordSortControl
                        v-if="words && words.meta.total > 0"
                        :sort="order.sort"
                        :direction="order.direction"
                        :default-directions="WORD_SORT_DEFAULT_DIRECTIONS"
                        :show-author="isMember"
                        :locked-sorts="lockedSorts"
                        @sort="sortWords"
                    />
                </div>

                <template v-if="!wordGroup.can.add_word">
                    <!-- Plain text like the other contribution messages: the header has the links to log in and register. -->
                    <p v-if="!isMember && wordGroup.accepts_contributions" class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ auth.isAuthenticated
                                ? $t('guest.verifyEmail')
                                : $t(restrictsContributionsToMembers(wordGroup) ? 'guest.contributeMembersOnly.wordGroup' : 'guest.contribute.wordGroup') }}
                    </p>
                    <p v-else-if="restrictsContributionsToMembers(wordGroup)" class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $t('wordGroups.show.membersOnly') }}
                    </p>
                    <p v-else class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $t('wordGroups.show.closed') }}
                    </p>
                </template>

                <p v-if="haveWordsFailed" class="mt-4 text-red-600 dark:text-red-400">
                    {{ $t('exports.panel.loadFailed') }}
                    <button type="button" class="underline" @click="loadWords">{{ $t('common.retry') }}</button>
                </p>

                <p v-else-if="words && words.meta.total === 0" class="mt-4 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-zinc-500 dark:text-zinc-400">
                    {{ $t('wordGroups.show.empty') }}
                </p>

                <template v-else-if="words">
                    <ul
                        class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs"
                        :class="{ 'opacity-60': areWordsLoading }"
                    >
                        <WordListItem
                            v-for="word in words.data"
                            :key="word.id"
                            :word="word"
                            @updated="onWordUpdated"
                        />
                    </ul>
                    <PaginationNav class="mt-4" :current-page="words.meta.current_page" :last-page="words.meta.last_page" />
                </template>
            </section>
        </template>
    </AppLayout>
</template>
