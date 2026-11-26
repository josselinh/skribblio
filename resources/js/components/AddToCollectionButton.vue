<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, shallowRef, useId, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { attachWordGroup } from '@/api/groupCollections';
import { fetchCollectionTargets } from '@/api/wordGroups';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { actionButtonClass } from '@/lib/actionsMenu';
import ModalDialog from '@/components/ModalDialog.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { useToasts } from '@/composables/useToasts';
import { rememberedTargetCollection, rememberTargetCollection } from '@/lib/targetCollection';
import { useAuthStore } from '@/stores/auth';
import type { CollectionTargetsResponse, ValidationErrorResponse } from '@/types/api';
import type { GroupCollection, WordGroup } from '@/types/models';

/**
 * "Ajouter à une collection" button of a group page, for logged-in users. A modal picks one of the collections the user
 * can add the group to (their favorites first, a search for the others); the chosen one is remembered on this device, so
 * that the next group pages offer "Ajouter à « X »" (confirmed in a dialog), followed by "Choisir une autre collection".
 * The remembered collection is checked on each page: when it cannot receive this group (other language, already in it,
 * rights lost), the button falls back to the picker.
 */
const props = defineProps<{
    wordGroup: Pick<WordGroup, 'id' | 'name' | 'language' | 'visibility'>;
}>();

const auth = useAuthStore();
const { showToast } = useToasts();
const { t } = useI18n();
const searchId = useId();

/** The remembered collection, when the group can be added to it. */
const rememberedTarget = ref<GroupCollection | null>(null);

async function loadRememberedTarget(): Promise<void> {
    rememberedTarget.value = null;
    const userId = auth.user?.id;
    const groupCollectionId = userId ? rememberedTargetCollection(userId) : null;
    if (!groupCollectionId) {
        return;
    }
    try {
        const { data } = await fetchCollectionTargets(props.wordGroup.id, { groupCollectionId });
        rememberedTarget.value = data.find((collection) => !collection.contains_word_group) ?? null;
    } catch {
        // The picker stays available.
    }
}

watch(() => props.wordGroup.id, loadRememberedTarget, { immediate: true });

const isAdding = ref(false);
const error = ref<string | null>(null);

/** Adds the group, remembers the collection for the next groups and confirms it in a notification. */
async function add(collection: GroupCollection): Promise<boolean> {
    isAdding.value = true;
    error.value = null;
    try {
        await attachWordGroup(collection.id, props.wordGroup.id);
        if (auth.user) {
            rememberTargetCollection(auth.user.id, collection.id);
        }
        rememberedTarget.value = null;
        showToast(t('addToCollection.done', { name: collection.name }));

        return true;
    } catch (caughtError) {
        error.value =
            isAxiosError<ValidationErrorResponse>(caughtError) && caughtError.response?.status === 422
                ? caughtError.response.data.message
                : t('addToCollection.failed');

        return false;
    } finally {
        isAdding.value = false;
    }
}

/* Confirmation of the addition to the remembered collection (kept while the dialog closes). */
const isConfirming = ref(false);
const confirmedTarget = shallowRef<GroupCollection | null>(null);

function askConfirmation(): void {
    error.value = null;
    confirmedTarget.value = rememberedTarget.value;
    isConfirming.value = true;
}

const confirmationMessage = computed(() => {
    const message = t('addToCollection.confirmation.message', {
        group: props.wordGroup.name,
        collection: confirmedTarget.value?.name ?? '',
    });

    return props.wordGroup.visibility === 'private' ? `${message} ${t('addToCollection.privateHint')}` : message;
});

async function confirmAddition(): Promise<void> {
    if (confirmedTarget.value && (await add(confirmedTarget.value))) {
        isConfirming.value = false;
    }
}

/* Picker: suggestions without a search, results of the search otherwise. */
const isPicking = ref(false);
const query = ref('');
const targets = shallowRef<CollectionTargetsResponse | null>(null);
const isLoading = ref(false);
const hasLoadFailed = ref(false);
const selectedId = ref<string | null>(null);
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let latestLoad = 0;

const selected = computed(() => targets.value?.data.find((collection) => collection.id === selectedId.value) ?? null);
const isSearching = computed(() => query.value.trim() !== '');

/** Loads the suggestions or the results of the search, ignoring outdated responses. */
async function loadTargets(): Promise<void> {
    const loadId = ++latestLoad;
    isLoading.value = true;
    hasLoadFailed.value = false;
    try {
        const response = await fetchCollectionTargets(props.wordGroup.id, { query: query.value.trim() });
        if (loadId === latestLoad) {
            targets.value = response;
            if (!response.data.some((collection) => collection.id === selectedId.value)) {
                selectedId.value = null;
            }
        }
    } catch {
        if (loadId === latestLoad) {
            hasLoadFailed.value = true;
        }
    } finally {
        if (loadId === latestLoad) {
            isLoading.value = false;
        }
    }
}

watch(query, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadTargets, 250);
});

function openPicker(): void {
    error.value = null;
    selectedId.value = null;
    targets.value = null;
    if (query.value === '') {
        void loadTargets();
    } else {
        query.value = '';
    }
    isPicking.value = true;
}

async function addSelected(): Promise<void> {
    if (selected.value && (await add(selected.value))) {
        isPicking.value = false;
    }
}

const hint = computed(() => {
    if (isSearching.value || !targets.value?.meta) {
        return null;
    }

    return targets.value.meta.suggestions === 'favorites' ? t('addToCollection.picker.favoritesHint') : t('addToCollection.picker.ownHint');
});
</script>

<template>
    <!-- Two entries of the actions menu: the remembered collection, then the picker. -->
    <template v-if="rememberedTarget">
        <button
            type="button"
            class="truncate"
            :class="actionButtonClass"
            aria-haspopup="dialog"
            @click="askConfirmation"
        >
            {{ $t('addToCollection.buttonTo', { name: rememberedTarget.name }) }}
        </button>
        <button
            type="button"
            :class="actionButtonClass"
            aria-haspopup="dialog"
            @click="openPicker"
        >
            {{ $t('addToCollection.chooseAnother') }}
        </button>
    </template>
    <button
        v-else
        type="button"
        :class="actionButtonClass"
        aria-haspopup="dialog"
        @click="openPicker"
    >
        {{ $t('addToCollection.button') }}
    </button>

    <ConfirmDialog
        v-model:open="isConfirming"
        tone="primary"
        :title="$t('addToCollection.confirmation.title', { name: confirmedTarget?.name ?? '' })"
        :message="confirmationMessage"
        :confirm-label="$t('addToCollection.picker.submit')"
        :processing-label="$t('addToCollection.processing')"
        :is-processing="isAdding"
        :error="error"
        @confirm="confirmAddition"
    />

    <ModalDialog v-model:open="isPicking" :title="$t('addToCollection.picker.title')" :is-dismissible="!isAdding">
        <p>{{ $t(`addToCollection.picker.languageHint.${wordGroup.language}`) }}</p>

        <label :for="searchId" class="sr-only">{{ $t('addToCollection.picker.searchLabel') }}</label>
        <input
            :id="searchId"
            v-model="query"
            type="search"
            autocomplete="off"
            :placeholder="$t('addToCollection.picker.searchPlaceholder')"
            class="mt-3 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 shadow-xs focus:border-brand-500 dark:focus:border-brand-400 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none"
        />

        <p v-if="hint" class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">{{ hint }}</p>

        <p v-if="hasLoadFailed" role="alert" class="mt-3 text-red-600 dark:text-red-400">
            {{ $t('addToCollection.picker.loadFailed') }}
            <button type="button" class="underline" @click="loadTargets">{{ $t('common.retry') }}</button>
        </p>
        <p v-else-if="!targets" class="mt-3 text-zinc-500 dark:text-zinc-400">{{ $t('common.loading') }}</p>
        <p v-else-if="targets.data.length === 0" class="mt-3 text-zinc-500 dark:text-zinc-400">
            {{ isSearching ? $t('addToCollection.picker.noResult') : $t('addToCollection.picker.noSuggestion') }}
        </p>
        <fieldset v-else class="mt-3" :class="{ 'opacity-60': isLoading }">
            <legend class="sr-only">{{ $t('addToCollection.picker.title') }}</legend>
            <ul class="max-h-72 space-y-2 overflow-y-auto">
                <li v-for="collection in targets.data" :key="collection.id">
                    <label
                        class="flex items-center gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3"
                        :class="
                            collection.contains_word_group
                                ? 'cursor-default opacity-60'
                                : 'cursor-pointer has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950'
                        "
                    >
                        <input
                            v-model="selectedId"
                            type="radio"
                            name="target-collection"
                            :value="collection.id"
                            :disabled="collection.contains_word_group || isAdding"
                        />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ collection.name }}</span>
                            <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                                <template v-if="collection.contains_word_group">{{ $t('addToCollection.picker.alreadyAdded') }} · </template>
                                {{ collection.creator?.name }}
                            </span>
                        </span>
                        <VisibilityBadge :visibility="collection.visibility" />
                    </label>
                </li>
            </ul>
        </fieldset>

        <p v-if="selected && wordGroup.visibility === 'private'" class="mt-3">{{ $t('addToCollection.privateHint') }}</p>
        <p v-if="error" role="alert" class="mt-3 text-red-600 dark:text-red-400">{{ error }}</p>

        <template #actions>
            <button
                type="button"
                :disabled="isAdding"
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="isPicking = false"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                type="button"
                :disabled="!selected || isAdding"
                class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="addSelected"
            >
                {{ isAdding ? $t('addToCollection.processing') : $t('addToCollection.picker.submit') }}
            </button>
        </template>
    </ModalDialog>
</template>
