<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { fetchGroupCollectionExport, fetchWordGroupExport, type ExportedWordGroup } from '@/api/exports';
import { createGroupCollection } from '@/api/groupCollections';
import { fetchGroupCollectionSearchResults } from '@/api/search';
import FormField from '@/components/FormField.vue';
import AppLayout from '@/components/AppLayout.vue';
import ContributionsField from '@/components/ContributionsField.vue';
import ItemSearch from '@/components/ItemSearch.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import WordGroupSearch from '@/components/WordGroupSearch.vue';
import WordListExport from '@/components/WordListExport.vue';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import { intlLocale } from '@/lib/i18n';
import type { GroupCollection, Visibility, WordGroup } from '@/types/models';

/**
 * Composes an export from groups, then lets the user save the selection as a new collection. Choosing a
 * collection adds its visible groups to the selection. Each group's words are loaded once, when it is
 * added; the list is then computed here. Nothing is saved until the user creates the collection.
 */
const router = useRouter();
const { showToast } = useToasts();
const { t } = useI18n();

const selectedWordGroups = ref<ExportedWordGroup[]>([]);
const selectedWordGroupIds = computed(() => selectedWordGroups.value.map((wordGroup) => wordGroup.id));
const hasSelection = computed(() => selectedWordGroups.value.length > 0);

/** Words of the selected groups, sorted by name, duplicates kept (the export options deal with them). */
const words = computed(() =>
    selectedWordGroups.value.flatMap((wordGroup) => wordGroup.words).sort((a, b) => a.name.localeCompare(b.name, intlLocale)),
);

const isAdding = ref(false);
const addError = ref<string | null>(null);
/** Outcome of the last collection added: how many groups were added, already there, or hidden. */
const addStatus = ref<string | null>(null);

/** Adds the groups that are not selected yet; returns how many were added. */
function appendWordGroups(wordGroups: ExportedWordGroup[]): number {
    const newWordGroups = wordGroups.filter((wordGroup) => !selectedWordGroupIds.value.includes(wordGroup.id));
    selectedWordGroups.value = [...selectedWordGroups.value, ...newWordGroups];

    return newWordGroups.length;
}

/** Loads something to add, showing a single error message when it fails. */
async function add(load: () => Promise<void>): Promise<void> {
    isAdding.value = true;
    addError.value = null;
    addStatus.value = null;
    try {
        await load();
    } catch {
        addError.value = t('exports.create.addFailed');
    } finally {
        isAdding.value = false;
    }
}

function addWordGroup(wordGroup: WordGroup): Promise<void> {
    return add(async () => {
        const groupWords = await fetchWordGroupExport(wordGroup.id);
        appendWordGroups([{ id: wordGroup.id, name: wordGroup.name, words: groupWords }]);
    });
}

function addGroupCollection(groupCollection: GroupCollection): Promise<void> {
    return add(async () => {
        const collectionExport = await fetchGroupCollectionExport(groupCollection.id);
        const addedCount = appendWordGroups(collectionExport.word_groups);
        addStatus.value = collectionAddedMessage(
            groupCollection.name,
            addedCount,
            collectionExport.word_groups.length - addedCount,
            collectionExport.hidden_word_groups_count,
        );
    });
}

function collectionAddedMessage(name: string, addedCount: number, alreadySelectedCount: number, hiddenCount: number): string {
    const parts = [
        t('exports.create.collectionAdded.added', { name }, addedCount),
    ];
    if (alreadySelectedCount > 0) {
        parts.push(t('exports.create.collectionAdded.alreadySelected', alreadySelectedCount));
    }
    if (hiddenCount > 0) {
        parts.push(t('exports.create.collectionAdded.hidden', hiddenCount));
    }

    return parts.join(' ');
}

function removeWordGroup(id: string): void {
    selectedWordGroups.value = selectedWordGroups.value.filter((wordGroup) => wordGroup.id !== id);
}

function removeAll(): void {
    selectedWordGroups.value = [];
    addStatus.value = null;
}

function searchGroupCollections(query: string): Promise<GroupCollection[]> {
    return fetchGroupCollectionSearchResults({ q: query, author: 'all', page: 1, perPage: 10 }).then(
        (results) => results.data,
    );
}

const form = useForm({ name: '', visibility: 'private' as Visibility, accepts_contributions: false, contributions_members_only: false });

/** Saves the selected groups as a new collection. */
async function saveAsGroupCollection(): Promise<void> {
    const wordGroupIds = selectedWordGroupIds.value;
    await form.submit(async (data) => {
        const groupCollection = await createGroupCollection({ ...data, word_group_ids: wordGroupIds });
        await router.push({ name: 'group-collections.show', params: { id: groupCollection.id } });
        showToast(t('exports.create.saved'));
    });
}

const saveError = computed(
    () =>
        form.errors.value.word_group_ids ??
        Object.entries(form.errors.value).find(([field]) => field.startsWith('word_group_ids.'))?.[1],
);

const tagClass = 'inline-flex items-center gap-1 rounded-full border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 py-1 pr-1 pl-3 text-sm';
const removeButtonClass = 'rounded-full px-1.5 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-zinc-100';
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-3xl">
            <h1 class="text-2xl font-semibold">{{ $t('dashboard.export.button') }}</h1>
            <p class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ $t('exports.create.description') }}
            </p>

            <section
                class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm"
                aria-labelledby="export-selection-title"
            >
                <h2 id="export-selection-title" class="text-lg font-semibold">{{ $t('exports.create.selection') }}</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="export-word-group-search" class="block text-sm font-medium">{{ $t('exports.create.addWordGroup') }}</label>
                        <WordGroupSearch
                            class="mt-1"
                            input-id="export-word-group-search"
                            :excluded-ids="selectedWordGroupIds"
                            :disabled="isAdding"
                            @select="addWordGroup"
                        />
                    </div>
                    <div>
                        <label for="export-group-collection-search" class="block text-sm font-medium">
                            {{ $t('exports.create.addGroupCollection') }}
                        </label>
                        <ItemSearch
                            class="mt-1"
                            input-id="export-group-collection-search"
                            :search="searchGroupCollections"
                            :placeholder="$t('exports.create.searchGroupCollection')"
                            :empty-message="$t('search.emptyGroupCollections')"
                            :disabled="isAdding"
                            @select="addGroupCollection"
                        />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $t('exports.create.addGroupCollectionHint') }}
                        </p>
                    </div>
                </div>

                <p v-if="isAdding" class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $t('exports.create.adding') }}</p>
                <p v-else-if="addError" role="alert" class="mt-4 text-sm text-red-600 dark:text-red-400">{{ addError }}</p>
                <p v-else-if="addStatus" role="status" class="mt-4 text-sm text-zinc-700 dark:text-zinc-300">{{ addStatus }}</p>

                <p v-if="!hasSelection" class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $t('exports.create.empty') }}</p>

                <template v-else>
                    <div class="mt-4 flex items-center justify-between gap-2">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $t('exports.create.selectedCount', selectedWordGroups.length) }}
                        </p>
                        <button type="button" class="text-sm text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100" @click="removeAll">
                            {{ $t('exports.create.removeAll') }}
                        </button>
                    </div>
                    <ul class="mt-2 flex flex-wrap gap-2" :aria-label="$t('wordGroups.picker.selected')">
                        <li v-for="wordGroup in selectedWordGroups" :key="wordGroup.id" :class="tagClass">
                            {{ wordGroup.name }}
                            <span class="text-zinc-500 dark:text-zinc-400">· {{ wordGroup.words.length }}</span>
                            <button
                                type="button"
                                :class="removeButtonClass"
                                :aria-label="$t('exports.create.removeWordGroup', { name: wordGroup.name })"
                                @click="removeWordGroup(wordGroup.id)"
                            >
                                ×
                            </button>
                        </li>
                    </ul>
                </template>
            </section>

            <template v-if="hasSelection">
                <section
                    class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm"
                    aria-labelledby="export-result-title"
                >
                    <h2 id="export-result-title" class="text-lg font-semibold">{{ $t('exports.create.result') }}</h2>

                    <p v-if="words.length === 0" class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $t('exports.panel.noWords') }}</p>
                    <WordListExport v-else class="mt-2" :words="words" />
                </section>

                <section
                    class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm"
                    aria-labelledby="export-save-title"
                >
                    <h2 id="export-save-title" class="text-lg font-semibold">{{ $t('exports.create.saveTitle') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $t('exports.create.saveDescription', selectedWordGroups.length) }}
                    </p>

                    <form class="mt-4 space-y-6" novalidate @submit.prevent="saveAsGroupCollection">
                        <FormField
                            id="name"
                            v-model="form.data.name"
                            :label="$t('groupCollections.fields.name')"
                            :error="form.errors.value.name"
                            required
                        />
                        <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
                        <ContributionsField
                            v-model="form.data.accepts_contributions"
                            v-model:members-only="form.data.contributions_members_only"
                            :visibility="form.data.visibility"
                            kind="group-collections"
                            :error="form.errors.value.accepts_contributions"
                            :members-only-error="form.errors.value.contributions_members_only"
                        />
                        <p v-if="saveError" role="alert" class="text-sm text-red-600 dark:text-red-400">{{ saveError }}</p>
                        <PrimaryButton :is-loading="form.isProcessing.value">{{ $t('groupCollections.create.submit') }}</PrimaryButton>
                    </form>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
