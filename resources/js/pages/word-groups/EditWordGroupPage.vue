<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { fetchWordGroup, updateWordGroup } from '@/api/wordGroups';
import { fetchWords } from '@/api/words';
import AccessManagement from '@/components/AccessManagement.vue';
import AddWordForm from '@/components/AddWordForm.vue';
import AppLayout from '@/components/AppLayout.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import NameVisibilityForm, { type NameVisibilityPayload } from '@/components/NameVisibilityForm.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import ShareButton from '@/components/ShareButton.vue';
import WordListEditor from '@/components/WordListEditor.vue';
import WordSortControl from '@/components/WordSortControl.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { useWordOrder, WORD_SORT_DEFAULT_DIRECTIONS } from '@/composables/useWordOrder';
import { itemCrumb, useBreadcrumb } from '@/lib/breadcrumb';
import { inactiveSharingReason, isShareable, restrictsContributionsToMembers } from '@/lib/contributions';
import { listPanelClass, listPanelHeaderClass } from '@/lib/sections';
import type { WordsResponse } from '@/types/api';
import type { AccessPurpose, WordGroup } from '@/types/models';

/**
 * Edit page of a group. The creator ("Gérer") finds its settings, its words, its sharing and its deletion; a contributor
 * ("Contribuer") only adding words and their own words (the others' are on the group page, they cannot change them),
 * unless the creator gave them a full sharing: they then find every word.
 */
const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const wordGroupId = computed(() => String(route.params.id));
const currentPage = computed(() => {
    const page = Number(route.query.page);

    return Number.isInteger(page) && page > 0 ? page : 1;
});

const wordGroup = ref<WordGroup | null>(null);
const groupStatus = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');

const isCreator = computed(() => wordGroup.value?.can.update ?? false);
/** The creator and the members with a full sharing manage every word; other contributors only their own. */
const managesEveryWord = computed(() => wordGroup.value?.can.manage_contributions ?? false);
const canEdit = computed(() => wordGroup.value !== null && (wordGroup.value.can.update || wordGroup.value.can.add_word));
const title = computed(() => (isCreator.value ? t('wordGroups.edit.title.manage') : t('wordGroups.edit.title.contribute')));
useBreadcrumb(
    () => (wordGroup.value ? title.value : null),
    () => (wordGroup.value ? itemCrumb('word-groups.show', wordGroup.value.id, wordGroup.value.name) : null),
);
usePageTitle(() => (wordGroup.value ? t('wordGroups.edit.pageTitle', { title: title.value, name: wordGroup.value.name }) : null));
/** A contributor only lists their own words: sorting them by author would be pointless. */
const { order, sortWords } = useWordOrder(() => (managesEveryWord.value ? [] : ['author']));

const words = ref<WordsResponse | null>(null);
const areWordsLoading = ref(false);
const haveWordsFailed = ref(false);
/** Unsaved renamings: sorting and pagination are hidden meanwhile, as they would lose them. */
const unsavedCount = ref(0);

async function loadWordGroup(): Promise<void> {
    groupStatus.value = 'loading';
    try {
        wordGroup.value = await fetchWordGroup(wordGroupId.value);
        groupStatus.value = 'loaded';
    } catch (error) {
        groupStatus.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'failed';
    }
}

async function loadWords(): Promise<void> {
    areWordsLoading.value = true;
    haveWordsFailed.value = false;
    try {
        words.value = await fetchWords(wordGroupId.value, currentPage.value, order.value, !managesEveryWord.value);
    } catch {
        haveWordsFailed.value = true;
    } finally {
        areWordsLoading.value = false;
    }
}

/** Settings being edited, saved or not: the "Partage" section follows them before they are saved. */
const settingsDraft = ref<NameVisibilityPayload | null>(null);
const currentSettings = computed(() => settingsDraft.value ?? wordGroup.value);

/**
 * Whether the settings keep the group shareable (open to contributions, private or members only): "Inviter" and the
 * type of sharing are offered. Unchecking "Ouvert aux contributions" hides them at once, even before saving.
 */
const isInvitationOffered = computed(
    () => (wordGroup.value?.can.share ?? false) && currentSettings.value !== null && isShareable(currentSettings.value),
);

/** Number of members, once `AccessManagement` loaded them: "Inviter" is highlighted while there is none. */
const membersCount = ref<number | null>(null);

/**
 * "Partage" is shown to the creator while the group is shareable, and otherwise as long as it has members, to manage
 * (or revoke) them. `AccessManagement` stays mounted meanwhile, to count them.
 */
const isSharingShown = computed(() => isInvitationOffered.value || (membersCount.value ?? 0) > 0);

/**
 * Reloads the words too: opening or closing contributions changes what the user can do with them. When the new
 * settings make the group shareable, the confirmation invites to send the invitation link.
 */
async function saveWordGroup(payload: NameVisibilityPayload): Promise<string | void> {
    const couldShare = wordGroup.value?.can.share ?? false;
    wordGroup.value = await updateWordGroup(wordGroupId.value, payload);
    await loadWords();

    return !couldShare && wordGroup.value.can.share ? t('editing.doneInvite.wordGroup') : undefined;
}

const accessPurpose = computed<AccessPurpose>(() =>
    wordGroup.value && restrictsContributionsToMembers(wordGroup.value) ? 'contribution' : 'access',
);

/** Adding, renaming or deleting words touches the group: keeps its modification date right without reloading it. */
function markWordGroupUpdated(): void {
    if (wordGroup.value) {
        wordGroup.value.updated_at = new Date().toISOString();
    }
}

/** A new word is listed first by default: shows page 1 so that it is visible. */
async function onWordAdded(): Promise<void> {
    markWordGroupUpdated();
    if (currentPage.value === 1) {
        await loadWords();
    } else {
        await router.push({ query: { ...route.query, page: undefined } });
    }
}

async function onWordsSaved(savedCount: number): Promise<void> {
    if (savedCount > 0) {
        markWordGroupUpdated();
    }
    await loadWords();
}

/** Goes back one page when the deletion emptied the current one. */
async function onWordsDeleted(deletedCount: number): Promise<void> {
    markWordGroupUpdated();
    const isPageEmptied = deletedCount === words.value?.data.length;
    if (isPageEmptied && currentPage.value > 1) {
        await router.push({ query: { ...route.query, page: String(currentPage.value - 1) } });
    } else {
        await loadWords();
    }
}

/** The words wait for the group: whose words to list depends on the user's rights. */
async function load(): Promise<void> {
    words.value = null;
    await loadWordGroup();
    if (groupStatus.value === 'loaded') {
        await loadWords();
    }
}

watch(wordGroupId, load, { immediate: true });
watch([currentPage, order, managesEveryWord], () => {
    if (groupStatus.value === 'loaded') {
        void loadWords();
    }
});

const sectionClass = `mt-6 ${listPanelClass}`;
const headingClass = 'text-lg font-semibold';
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
            <h1 class="text-2xl font-semibold">{{ title }}</h1>

            <p v-if="!canEdit" class="mt-4 text-zinc-500 dark:text-zinc-400">{{ $t('wordGroups.edit.forbidden') }}</p>

            <template v-else>
                <section v-if="isCreator" :class="sectionClass" aria-labelledby="settings-heading">
                    <div :class="listPanelHeaderClass">
                        <h2 id="settings-heading" :class="headingClass">{{ $t('wordGroups.edit.sections.settings') }}</h2>
                    </div>
                    <NameVisibilityForm
                        :key="wordGroup.id"
                        class="mt-4"
                        :initial="{
                            name: wordGroup.name,
                            visibility: wordGroup.visibility,
                            accepts_contributions: wordGroup.accepts_contributions,
                            contributions_members_only: wordGroup.contributions_members_only,
                        }"
                        kind="word-groups"
                        :name-label="$t('wordGroups.fields.name')"
                        :save="saveWordGroup"
                        @change="settingsDraft = $event"
                    />
                </section>

                <section
                    v-if="wordGroup.can.manage_access"
                    v-show="isSharingShown"
                    :class="sectionClass"
                    aria-labelledby="sharing-heading"
                >
                    <div :class="listPanelHeaderClass">
                        <h2 id="sharing-heading" :class="headingClass">{{ $t('wordGroups.edit.sections.sharing') }}</h2>
                        <ShareButton
                            v-if="isInvitationOffered"
                            kind="word-groups"
                            :id="wordGroup.id"
                            :purpose="accessPurpose"
                            :prominent="membersCount === 0"
                        />
                    </div>
                    <AccessManagement
                        class="mt-4"
                        kind="word-groups"
                        :id="wordGroup.id"
                        :purpose="accessPurpose"
                        :inactive-reason="isInvitationOffered || !currentSettings ? null : inactiveSharingReason(currentSettings)"
                        @members-count="membersCount = $event"
                    />
                </section>

                <section :class="sectionClass" aria-labelledby="words-heading">
                    <div :class="listPanelHeaderClass">
                        <h2 id="words-heading" :class="headingClass">
                            {{ managesEveryWord ? $t('wordGroups.edit.sections.words') : $t('wordGroups.edit.sections.ownWords') }}
                            <span v-if="words" class="font-normal text-zinc-500 dark:text-zinc-400">({{ words.meta.total }})</span>
                        </h2>
                    </div>
                    <AddWordForm v-if="wordGroup.can.add_word" class="mt-4" :word-group-id="wordGroup.id" @added="onWordAdded" />

                    <p v-if="haveWordsFailed" class="mt-6 text-red-600 dark:text-red-400">
                        {{ $t('exports.panel.loadFailed') }}
                        <button type="button" class="underline" @click="loadWords">{{ $t('common.retry') }}</button>
                    </p>

                    <p v-else-if="words && words.meta.total === 0" class="mt-6 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-zinc-500 dark:text-zinc-400">
                        {{ managesEveryWord ? $t('wordGroups.show.empty') : $t('wordGroups.edit.noOwnWords') }}
                    </p>

                    <template v-else-if="words">
                        <div v-if="unsavedCount === 0" class="mt-6 flex justify-end">
                            <WordSortControl
                                :sort="order.sort"
                                :direction="order.direction"
                                :default-directions="WORD_SORT_DEFAULT_DIRECTIONS"
                                :show-author="managesEveryWord"
                                :locked-sorts="[]"
                                @sort="sortWords"
                            />
                        </div>
                        <WordListEditor
                            :class="[unsavedCount === 0 ? 'mt-2' : 'mt-6', { 'opacity-60': areWordsLoading }]"
                            :word-group-id="wordGroup.id"
                            :words="words.data"
                            @saved="onWordsSaved"
                            @deleted="onWordsDeleted"
                            @changed="unsavedCount = $event"
                        />
                        <PaginationNav v-if="unsavedCount === 0" class="mt-4" :current-page="words.meta.current_page" :last-page="words.meta.last_page" />
                        <p v-else-if="words.meta.last_page > 1" class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $t('wordGroups.edit.unsavedHint') }}
                        </p>
                    </template>
                </section>

                <section v-if="wordGroup.can.delete" :class="sectionClass" aria-labelledby="deletion-heading">
                    <div :class="listPanelHeaderClass">
                        <h2 id="deletion-heading" :class="headingClass">{{ $t('wordGroups.edit.sections.deletion') }}</h2>
                    </div>
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $t('deletion.warning.wordGroup') }}</p>
                    <div class="mt-4">
                        <DeleteButton kind="word-groups" :id="wordGroup.id" />
                    </div>
                </section>
            </template>
        </template>
    </AppLayout>
</template>
