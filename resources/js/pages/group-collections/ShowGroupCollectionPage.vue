<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { attachWordGroup, detachWordGroups, fetchGroupCollection, updateGroupCollection } from '@/api/groupCollections';
import { requestHiddenWordGroupsAccess } from '@/api/sharing';
import AppLayout from '@/components/AppLayout.vue';
import AccessManagement from '@/components/AccessManagement.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import DuplicateButton from '@/components/DuplicateButton.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import LeaveButton from '@/components/LeaveButton.vue';
import ShareButton from '@/components/ShareButton.vue';
import ExportPanel from '@/components/ExportPanel.vue';
import EditableNameVisibility, { type NameVisibilityPayload } from '@/components/EditableNameVisibility.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import WordGroupSearch from '@/components/WordGroupSearch.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { useToasts } from '@/composables/useToasts';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import { useAuthStore } from '@/stores/auth';
import type { ValidationErrorResponse } from '@/types/api';
import type { AccessPurpose, GroupCollectionDetail, WordGroup } from '@/types/models';

const route = useRoute();
const { t } = useI18n();
const auth = useAuthStore();

/** Visitors (and unverified users, treated the same way by the API) can neither contribute nor request access. */
const isMember = computed(() => auth.isVerified);

const groupCollectionId = computed(() => String(route.params.id));

const groupCollection = ref<GroupCollectionDetail | null>(null);
const status = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
const isUpdatingGroups = ref(false);
usePageTitle(() => groupCollection.value?.name);

const wordGroupIds = computed(() => groupCollection.value?.word_groups.map((wordGroup) => wordGroup.id) ?? []);

/** Includes the groups hidden from the user. */
const totalWordGroupsCount = computed(() =>
    groupCollection.value ? groupCollection.value.word_groups.length + groupCollection.value.hidden_word_groups_count : 0,
);

const accessPurpose = computed<AccessPurpose>(() =>
    groupCollection.value && restrictsContributionsToMembers(groupCollection.value) ? 'contribution' : 'access',
);

const hiddenGroupsMessage = computed(() => {
    const count = groupCollection.value?.hidden_word_groups_count ?? 0;

    return count === 0 ? null : t('groupCollections.show.hidden.message', count);
});

const exportNote = computed(() =>
    hiddenGroupsMessage.value ? t('groupCollections.show.hidden.exportNote') : null,
);

/** Whether some hidden groups were not requested yet. */
const canRequestHiddenGroups = computed(
    () =>
        groupCollection.value !== null &&
        groupCollection.value.hidden_word_groups_requested_count < groupCollection.value.hidden_word_groups_count,
);
const isRequestingHiddenGroups = ref(false);
const hiddenGroupsRequestError = ref<string | null>(null);
const isConfirmingHiddenGroupsRequest = ref(false);

/** Hidden groups not requested yet, which the request will be sent for. */
const unrequestedHiddenGroupsCount = computed(() =>
    groupCollection.value ? groupCollection.value.hidden_word_groups_count - groupCollection.value.hidden_word_groups_requested_count : 0,
);

function askHiddenGroupsRequestConfirmation(): void {
    hiddenGroupsRequestError.value = null;
    isConfirmingHiddenGroupsRequest.value = true;
}

/** Sends the confirmed request, then reloads the collection; errors stay in the dialog. */
async function requestHiddenGroups(): Promise<void> {
    isRequestingHiddenGroups.value = true;
    hiddenGroupsRequestError.value = null;
    try {
        await requestHiddenWordGroupsAccess(groupCollectionId.value);
        isConfirmingHiddenGroupsRequest.value = false;
        await loadGroupCollection();
        showToast(t('groupCollections.show.hidden.confirmation.done'));
    } catch {
        hiddenGroupsRequestError.value = t('common.unexpectedError');
    } finally {
        isRequestingHiddenGroups.value = false;
    }
}

/** After leaving a public collection, which stays visible: reloads it, as the user's rights changed. */
async function onLeft(): Promise<void> {
    await loadGroupCollection();
}

async function loadGroupCollection(): Promise<void> {
    try {
        groupCollection.value = await fetchGroupCollection(groupCollectionId.value);
        status.value = 'loaded';
        selectedWordGroupIds.value = selectedWordGroupIds.value.filter((id) =>
            removableWordGroups.value.some((wordGroup) => wordGroup.id === id),
        );
    } catch (error) {
        status.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'failed';
    }
}

/** Reloads the collection: opening or closing contributions changes which groups the user can remove. */
async function saveGroupCollection(payload: NameVisibilityPayload): Promise<void> {
    await updateGroupCollection(groupCollectionId.value, payload);
    await loadGroupCollection();
}

const { showToast } = useToasts();

/** Error of a 422 response (e.g. removing every group), or a generic message. */
function groupChangeErrorMessage(error: unknown): string {
    return isAxiosError<ValidationErrorResponse>(error) && error.response?.status === 422
        ? error.response.data.message
        : t('common.unexpectedError');
}

/** Group picked in the search, being confirmed in `ConfirmDialog` before it is added. */
const wordGroupToAdd = ref<WordGroup | null>(null);
const additionError = ref<string | null>(null);

const isConfirmingAddition = computed({
    get: () => wordGroupToAdd.value !== null,
    set: (isOpen: boolean) => {
        if (!isOpen) {
            wordGroupToAdd.value = null;
        }
    },
});

const additionMessage = computed(() => {
    const wordGroup = wordGroupToAdd.value;
    if (!wordGroup) {
        return '';
    }

    return t(`groupCollections.show.addition.message.${wordGroup.visibility}`, { name: wordGroup.name });
});

function confirmAddition(wordGroup: WordGroup): void {
    additionError.value = null;
    wordGroupToAdd.value = wordGroup;
}

/** Adds the confirmed group, then reloads the collection; errors stay in the dialog. */
async function addWordGroup(): Promise<void> {
    const wordGroup = wordGroupToAdd.value;
    if (!wordGroup) {
        return;
    }
    isUpdatingGroups.value = true;
    additionError.value = null;
    try {
        await attachWordGroup(groupCollectionId.value, wordGroup.id);
        wordGroupToAdd.value = null;
        await loadGroupCollection();
        showToast(t('groupCollections.show.addition.done', { name: wordGroup.name }));
    } catch (error) {
        additionError.value = groupChangeErrorMessage(error);
    } finally {
        isUpdatingGroups.value = false;
    }
}

/**
 * Selection mode: the groups the user may remove become selectable, and are removed together (possibly
 * all of them).
 */
const isSelectingGroups = ref(false);
const selectedWordGroupIds = ref<string[]>([]);
const removableWordGroups = computed(
    () => groupCollection.value?.word_groups.filter((wordGroup) => wordGroup.can.remove_from_collection) ?? [],
);
const canRemoveWordGroups = computed(() => removableWordGroups.value.length > 0);
const areAllRemovableWordGroupsSelected = computed(
    () => removableWordGroups.value.length > 0 && selectedWordGroupIds.value.length === removableWordGroups.value.length,
);
const isConfirmingRemoval = ref(false);
const removalError = ref<string | null>(null);

function startSelectingGroups(): void {
    selectedWordGroupIds.value = [];
    isSelectingGroups.value = true;
}

function stopSelectingGroups(): void {
    isSelectingGroups.value = false;
    selectedWordGroupIds.value = [];
}

function toggleAllRemovableWordGroups(): void {
    selectedWordGroupIds.value = areAllRemovableWordGroupsSelected.value
        ? []
        : removableWordGroups.value.map((wordGroup) => wordGroup.id);
}

function askRemovalConfirmation(): void {
    removalError.value = null;
    isConfirmingRemoval.value = true;
}

/** Removes the selected groups, then leaves selection mode and reloads the collection; errors stay in the dialog. */
async function removeSelectedWordGroups(): Promise<void> {
    const removedCount = selectedWordGroupIds.value.length;
    isUpdatingGroups.value = true;
    removalError.value = null;
    try {
        await detachWordGroups(groupCollectionId.value, selectedWordGroupIds.value);
        isConfirmingRemoval.value = false;
        stopSelectingGroups();
        await loadGroupCollection();
        showToast(t('groupCollections.show.removal.done', removedCount));
    } catch (error) {
        removalError.value = groupChangeErrorMessage(error);
    } finally {
        isUpdatingGroups.value = false;
    }
}

watch(
    groupCollectionId,
    () => {
        status.value = 'loading';
        stopSelectingGroups();
        void loadGroupCollection();
    },
    { immediate: true },
);
</script>

<template>
    <AppLayout>

        <p v-if="status === 'loading'" class="mt-4 text-zinc-500 dark:text-zinc-400">{{ $t('common.loading') }}</p>

        <div v-else-if="status === 'not-found'" class="mt-4">
            <h1 class="text-2xl font-semibold">{{ $t('groupCollections.show.notFound.title') }}</h1>
            <p class="mt-2 text-zinc-500 dark:text-zinc-400">{{ $t('groupCollections.show.notFound.description') }}</p>
        </div>

        <p v-else-if="status === 'failed'" class="mt-4 text-red-600 dark:text-red-400">
            {{ $t('groupCollections.show.loadFailed') }}
            <button type="button" class="underline" @click="loadGroupCollection">{{ $t('common.retry') }}</button>
        </p>

        <template v-else-if="groupCollection">
            <div class="mt-2">
                <EditableNameVisibility
                    :name="groupCollection.name"
                    :language="groupCollection.language"
                    :visibility="groupCollection.visibility"
                    :accepts-contributions="groupCollection.accepts_contributions"
                    :contributions-members-only="groupCollection.contributions_members_only"
                    kind="group-collections"
                    :creator="groupCollection.creator"
                    :can-edit="groupCollection.can.update"
                    :name-label="$t('groupCollections.fields.name')"
                    :save="saveGroupCollection"
                >
                    <template v-if="groupCollection.is_favorite !== undefined" #after-title>
                        <FavoriteButton
                            v-model="groupCollection.is_favorite"
                            kind="group-collections"
                            :id="groupCollection.id"
                            :name="groupCollection.name"
                        />
                    </template>
                    <template #actions>
                        <DuplicateButton
                            v-if="groupCollection.can.duplicate && groupCollection.word_groups.length > 0"
                            kind="group-collections"
                            :id="groupCollection.id"
                        />
                        <ShareButton
                            v-if="groupCollection.can.share"
                            kind="group-collections"
                            :id="groupCollection.id"
                            :purpose="accessPurpose"
                        />
                        <DeleteButton v-if="groupCollection.can.delete" kind="group-collections" :id="groupCollection.id" />
                        <LeaveButton
                            v-if="groupCollection.can.leave"
                            kind="group-collections"
                            :id="groupCollection.id"
                            :visibility="groupCollection.visibility"
                            @left="onLeft"
                        />
                    </template>
                </EditableNameVisibility>
            </div>

            <ExportPanel class="mt-4" kind="group-collections" :id="groupCollection.id" :note="exportNote" :locked="!isMember" />

            <div
                v-if="hiddenGroupsMessage"
                role="note"
                class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-800 dark:text-amber-200"
            >
                <p>{{ hiddenGroupsMessage }}</p>
                <button
                    v-if="isMember && canRequestHiddenGroups"
                    type="button"
                    :disabled="isRequestingHiddenGroups"
                    class="rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-zinc-900 px-3 py-1.5 font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40 disabled:opacity-60"
                    aria-haspopup="dialog"
                    @click="askHiddenGroupsRequestConfirmation"
                >
                    {{ $t('groupCollections.show.hidden.request') }}
                </button>
                <p v-else-if="isMember" class="font-medium">{{ $t('groupCollections.show.hidden.requested') }}</p>
            </div>
            <ConfirmDialog
                v-model:open="isConfirmingHiddenGroupsRequest"
                :title="$t('groupCollections.show.hidden.confirmation.title')"
                :message="$t('groupCollections.show.hidden.confirmation.message', unrequestedHiddenGroupsCount)"
                :confirm-label="$t('groupCollections.show.hidden.confirmation.confirm')"
                :processing-label="$t('groupCollections.show.hidden.confirmation.processing')"
                tone="primary"
                :is-processing="isRequestingHiddenGroups"
                :error="hiddenGroupsRequestError"
                @confirm="requestHiddenGroups"
            />

            <AccessManagement
                v-if="groupCollection.can.share"
                class="mt-8"
                kind="group-collections"
                :id="groupCollection.id"
                :purpose="accessPurpose"
            />

            <section class="mt-8" aria-labelledby="collection-groups-title">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 id="collection-groups-title" class="text-lg font-semibold">
                        {{ $t('common.wordGroups') }} <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ totalWordGroupsCount }})</span>
                    </h2>
                    <button
                        v-if="canRemoveWordGroups && !isSelectingGroups"
                        type="button"
                        class="text-sm font-medium text-brand-700 dark:text-brand-300 hover:underline"
                        @click="startSelectingGroups"
                    >
                        {{ $t('groupCollections.show.editGroups') }}
                    </button>
                </div>

                <div v-if="groupCollection.can.add_word_group" class="mt-3">
                    <label for="add-word-group" class="sr-only">{{ $t('exports.create.addWordGroup') }}</label>
                    <WordGroupSearch
                        input-id="add-word-group"
                        :language="groupCollection.language"
                        :excluded-ids="wordGroupIds"
                        :disabled="isUpdatingGroups"
                        @select="confirmAddition"
                    />
                </div>

                <!-- Plain text like the other contribution messages: the header has the links to log in and register. -->
                <p v-else-if="!isMember && groupCollection.accepts_contributions" class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ auth.isAuthenticated ? $t('guest.verifyEmail') : $t('guest.contribute.groupCollection') }}
                </p>
                <p v-else-if="restrictsContributionsToMembers(groupCollection)" class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $t('groupCollections.show.membersOnly') }}
                </p>
                <p v-else class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $t('groupCollections.show.closed') }}
                </p>

                <p
                    v-if="groupCollection.word_groups.length === 0"
                    class="mt-4 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-zinc-500 dark:text-zinc-400"
                >
                    {{ totalWordGroupsCount === 0 ? $t('groupCollections.show.empty') : $t('groupCollections.show.noVisibleGroup') }}
                </p>

                <div
                    v-if="isSelectingGroups && groupCollection.word_groups.length > 0"
                    class="mt-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 px-4 py-2 text-sm"
                >
                    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :checked="areAllRemovableWordGroupsSelected"
                                :indeterminate="selectedWordGroupIds.length > 0 && !areAllRemovableWordGroupsSelected"
                                class="size-4 rounded border-zinc-300 dark:border-zinc-700 accent-brand-600 dark:accent-brand-400"
                                @change="toggleAllRemovableWordGroups"
                            />
                            <span v-if="selectedWordGroupIds.length === 0">{{ $t('common.selectAll') }}</span>
                            <span v-else>{{ $t('exports.create.selectedCount', selectedWordGroupIds.length) }}</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100"
                                @click="stopSelectingGroups"
                            >
                                {{ $t('common.cancel') }}
                            </button>
                            <button
                                type="button"
                                :disabled="selectedWordGroupIds.length === 0 || isUpdatingGroups"
                                class="font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 disabled:cursor-not-allowed disabled:opacity-50"
                                aria-haspopup="dialog"
                                @click="askRemovalConfirmation"
                            >
                                {{ $t('common.deleteSelection') }}
                            </button>
                        </div>
                    </div>
                </div>

                <ul
                    v-if="groupCollection.word_groups.length > 0"
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    :class="{ 'opacity-60': isUpdatingGroups }"
                >
                    <li v-for="wordGroup in groupCollection.word_groups" :key="wordGroup.id" class="relative">
                        <WordGroupCard
                            :word-group="wordGroup"
                            :class="{ 'opacity-50': isSelectingGroups && !wordGroup.can.remove_from_collection }"
                        />
                        <label
                            v-if="isSelectingGroups && wordGroup.can.remove_from_collection"
                            class="absolute inset-0 z-20 flex cursor-pointer items-end justify-end rounded-xl p-3 ring-inset has-checked:bg-brand-50/50 dark:has-checked:bg-brand-950/40 has-checked:ring-2 has-checked:ring-brand-500 dark:has-checked:ring-brand-400 has-focus-visible:outline-2 has-focus-visible:outline-brand-600 dark:has-focus-visible:outline-brand-400"
                        >
                            <input
                                v-model="selectedWordGroupIds"
                                type="checkbox"
                                :value="wordGroup.id"
                                class="size-5 rounded border-zinc-300 dark:border-zinc-700 accent-brand-600 dark:accent-brand-400 focus-visible:outline-none"
                            />
                            <span class="sr-only">{{ $t('words.editor.selectWord', { name: wordGroup.name }) }}</span>
                        </label>
                    </li>
                </ul>

                <ConfirmDialog
                    v-model:open="isConfirmingAddition"
                    :title="$t('groupCollections.show.addition.title')"
                    :message="additionMessage"
                    :confirm-label="$t('words.add.submit')"
                    :processing-label="$t('groupCollections.show.addition.processing')"
                    tone="primary"
                    :is-processing="isUpdatingGroups"
                    :error="additionError"
                    @confirm="addWordGroup"
                />
                <ConfirmDialog
                    v-model:open="isConfirmingRemoval"
                    :title="$t('groupCollections.show.removal.title', selectedWordGroupIds.length)"
                    :message="$t('groupCollections.show.removal.message', selectedWordGroupIds.length)"
                    :confirm-label="$t('deletion.button')"
                    :processing-label="$t('deletion.processing')"
                    :is-processing="isUpdatingGroups"
                    :error="removalError"
                    @confirm="removeSelectedWordGroups"
                />
            </section>
        </template>
    </AppLayout>
</template>
