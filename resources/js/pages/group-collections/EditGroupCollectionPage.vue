<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { attachWordGroup, detachWordGroups, fetchGroupCollection, updateGroupCollection } from '@/api/groupCollections';
import AppIcon from '@/components/AppIcon.vue';
import AccessManagement from '@/components/AccessManagement.vue';
import AppLayout from '@/components/AppLayout.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import NameVisibilityForm, { type NameVisibilityPayload } from '@/components/NameVisibilityForm.vue';
import ShareButton from '@/components/ShareButton.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import WordGroupSearch from '@/components/WordGroupSearch.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { useToasts } from '@/composables/useToasts';
import { itemCrumb, useBreadcrumb } from '@/lib/breadcrumb';
import { cardGridItemClass } from '@/lib/cards';
import { inactiveSharingReason, isShareable, restrictsContributionsToMembers } from '@/lib/contributions';
import { listPanelClass, listPanelHeaderClass } from '@/lib/sections';
import { useAuthStore } from '@/stores/auth';
import type { ValidationErrorResponse } from '@/types/api';
import type { AccessPurpose, GroupCollectionDetail, WordGroup } from '@/types/models';

/**
 * Edit page of a collection. The creator ("Gérer") finds its settings, its groups, its sharing and its deletion; a
 * contributor ("Contribuer") only adding groups and the groups they added (the others are on the collection page,
 * they cannot remove them), unless the creator gave them a full sharing: they then find every group. The listed
 * groups are selectable at once, to remove several together.
 */
const route = useRoute();
const { t } = useI18n();
const { showToast } = useToasts();
const auth = useAuthStore();

const groupCollectionId = computed(() => String(route.params.id));

const groupCollection = ref<GroupCollectionDetail | null>(null);
const status = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
const isUpdatingGroups = ref(false);

const isCreator = computed(() => groupCollection.value?.can.update ?? false);
/** The creator and the members with a full sharing manage every group; other contributors only those they added. */
const managesEveryGroup = computed(() => groupCollection.value?.can.manage_contributions ?? false);
const canEdit = computed(
    () => groupCollection.value !== null && (groupCollection.value.can.update || groupCollection.value.can.add_word_group),
);
const title = computed(() => (isCreator.value ? t('groupCollections.edit.title.manage') : t('groupCollections.edit.title.contribute')));
useBreadcrumb(
    () => (groupCollection.value ? title.value : null),
    () => (groupCollection.value ? itemCrumb('group-collections.show', groupCollection.value.id, groupCollection.value.name) : null),
);
usePageTitle(() =>
    groupCollection.value ? t('groupCollections.edit.pageTitle', { title: title.value, name: groupCollection.value.name }) : null,
);

/** Every visible group is offered to the creator and full members, only the ones they added to a contributor. */
const listedWordGroups = computed(() => {
    const wordGroups = groupCollection.value?.word_groups ?? [];

    return managesEveryGroup.value ? wordGroups : wordGroups.filter((wordGroup) => wordGroup.added_by?.id === auth.user?.id);
});

/** Groups already in the collection are left out of the search. */
const wordGroupIds = computed(() => groupCollection.value?.word_groups.map((wordGroup) => wordGroup.id) ?? []);

const accessPurpose = computed<AccessPurpose>(() =>
    groupCollection.value && restrictsContributionsToMembers(groupCollection.value) ? 'contribution' : 'access',
);

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
/** Settings being edited, saved or not: the "Partage" section follows them before they are saved. */
const settingsDraft = ref<NameVisibilityPayload | null>(null);
const currentSettings = computed(() => settingsDraft.value ?? groupCollection.value);

/**
 * Whether the settings keep the collection shareable (open to contributions, private or members only): "Inviter" and the
 * type of sharing are offered. Unchecking "Ouvert aux contributions" hides them at once, even before saving.
 */
const isInvitationOffered = computed(
    () => (groupCollection.value?.can.share ?? false) && currentSettings.value !== null && isShareable(currentSettings.value),
);

/** Number of members, once `AccessManagement` loaded them: "Inviter" is highlighted while there is none. */
const membersCount = ref<number | null>(null);

/**
 * "Partage" is shown to the creator while the collection is shareable, and otherwise as long as it has members, to manage
 * (or revoke) them. `AccessManagement` stays mounted meanwhile, to count them.
 */
const isSharingShown = computed(() => isInvitationOffered.value || (membersCount.value ?? 0) > 0);

/** When the new settings make the collection shareable, the confirmation invites to send the invitation link. */
async function saveGroupCollection(payload: NameVisibilityPayload): Promise<string | void> {
    const couldShare = groupCollection.value?.can.share ?? false;
    await updateGroupCollection(groupCollectionId.value, payload);
    await loadGroupCollection();

    return !couldShare && groupCollection.value?.can.share ? t('editing.doneInvite.groupCollection') : undefined;
}

/** Error of a 422 response, or a generic message. */
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

const selectedWordGroupIds = ref<string[]>([]);
const removableWordGroups = computed(() => listedWordGroups.value.filter((wordGroup) => wordGroup.can.remove_from_collection));
const areAllRemovableWordGroupsSelected = computed(
    () => removableWordGroups.value.length > 0 && selectedWordGroupIds.value.length === removableWordGroups.value.length,
);
const isConfirmingRemoval = ref(false);
const removalError = ref<string | null>(null);

function toggleAllRemovableWordGroups(): void {
    selectedWordGroupIds.value = areAllRemovableWordGroupsSelected.value
        ? []
        : removableWordGroups.value.map((wordGroup) => wordGroup.id);
}

function askRemovalConfirmation(): void {
    removalError.value = null;
    isConfirmingRemoval.value = true;
}

/** Removes the selected groups, then reloads the collection; errors stay in the dialog. */
async function removeSelectedWordGroups(): Promise<void> {
    const removedCount = selectedWordGroupIds.value.length;
    isUpdatingGroups.value = true;
    removalError.value = null;
    try {
        await detachWordGroups(groupCollectionId.value, selectedWordGroupIds.value);
        isConfirmingRemoval.value = false;
        selectedWordGroupIds.value = [];
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
        selectedWordGroupIds.value = [];
        void loadGroupCollection();
    },
    { immediate: true },
);

const sectionClass = `mt-6 ${listPanelClass}`;
const headingClass = 'text-lg font-semibold';
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
            <h1 class="text-2xl font-semibold">{{ title }}</h1>

            <p v-if="!canEdit" class="mt-4 text-zinc-500 dark:text-zinc-400">{{ $t('groupCollections.edit.forbidden') }}</p>

            <template v-else>
                <section v-if="isCreator" :class="sectionClass" aria-labelledby="settings-heading">
                    <div :class="listPanelHeaderClass">
                        <h2 id="settings-heading" :class="headingClass">{{ $t('groupCollections.edit.sections.settings') }}</h2>
                    </div>
                    <NameVisibilityForm
                        :key="groupCollection.id"
                        class="mt-4"
                        :initial="{
                            name: groupCollection.name,
                            visibility: groupCollection.visibility,
                            accepts_contributions: groupCollection.accepts_contributions,
                            contributions_members_only: groupCollection.contributions_members_only,
                        }"
                        kind="group-collections"
                        :name-label="$t('groupCollections.fields.name')"
                        :save="saveGroupCollection"
                        @change="settingsDraft = $event"
                    />
                </section>

                <section
                    v-if="groupCollection.can.manage_access"
                    v-show="isSharingShown"
                    :class="sectionClass"
                    aria-labelledby="sharing-heading"
                >
                    <div :class="listPanelHeaderClass">
                        <h2 id="sharing-heading" :class="headingClass">{{ $t('groupCollections.edit.sections.sharing') }}</h2>
                        <ShareButton
                            v-if="isInvitationOffered"
                            kind="group-collections"
                            :id="groupCollection.id"
                            :purpose="accessPurpose"
                            :prominent="membersCount === 0"
                        />
                    </div>
                    <AccessManagement
                        class="mt-4"
                        kind="group-collections"
                        :id="groupCollection.id"
                        :purpose="accessPurpose"
                        :inactive-reason="isInvitationOffered || !currentSettings ? null : inactiveSharingReason(currentSettings)"
                        @members-count="membersCount = $event"
                    />
                </section>

                <section :class="sectionClass" aria-labelledby="groups-heading">
                    <div :class="listPanelHeaderClass">
                        <h2 id="groups-heading" :class="headingClass">
                            {{ managesEveryGroup ? $t('groupCollections.edit.sections.groups') : $t('groupCollections.edit.sections.ownGroups') }}
                            <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ listedWordGroups.length }})</span>
                        </h2>
                    </div>

                    <div v-if="groupCollection.can.add_word_group" class="mt-4">
                        <label for="add-word-group" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $t('groupCollections.show.addWordGroup') }}
                        </label>
                        <WordGroupSearch
                            class="mt-2"
                            input-id="add-word-group"
                            :language="groupCollection.language"
                            :excluded-ids="wordGroupIds"
                            :disabled="isUpdatingGroups"
                            @select="confirmAddition"
                        />
                    </div>

                    <p
                        v-if="listedWordGroups.length === 0"
                        class="mt-6 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-zinc-500 dark:text-zinc-400"
                    >
                        {{ managesEveryGroup ? $t('groupCollections.show.empty') : $t('groupCollections.edit.noOwnGroups') }}
                    </p>

                    <template v-else>
                        <div
                            v-if="removableWordGroups.length > 0"
                            class="mt-6 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 px-4 py-2 text-sm"
                        >
                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    :checked="areAllRemovableWordGroupsSelected"
                                    :indeterminate="selectedWordGroupIds.length > 0 && !areAllRemovableWordGroupsSelected"
                                    class="size-4 rounded border-zinc-300 dark:border-zinc-700 accent-brand-600 dark:accent-brand-400"
                                    @change="toggleAllRemovableWordGroups"
                                />
                                <span v-if="selectedWordGroupIds.length === 0">{{ $t('common.selectAll') }}</span>
                                <span v-else>{{ $t('groupCollections.show.selectedCount', selectedWordGroupIds.length) }}</span>
                            </label>
                            <button
                                type="button"
                                :disabled="selectedWordGroupIds.length === 0 || isUpdatingGroups"
                                class="inline-flex items-center gap-1.5 font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 disabled:cursor-not-allowed disabled:opacity-50"
                                aria-haspopup="dialog"
                                @click="askRemovalConfirmation"
                            >
                                <AppIcon name="delete" />
                                {{ $t('common.deleteSelection') }}
                            </button>
                        </div>

                        <ul
                            class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            :class="{ 'opacity-60': isUpdatingGroups }"
                        >
                            <li v-for="wordGroup in listedWordGroups" :key="wordGroup.id" class="relative" :class="cardGridItemClass">
                                <WordGroupCard
                                    :word-group="wordGroup"
                                    :class="{ 'opacity-50': !wordGroup.can.remove_from_collection }"
                                />
                                <label
                                    v-if="wordGroup.can.remove_from_collection"
                                    class="absolute inset-0 z-20 flex cursor-pointer items-end justify-end rounded-xl pr-3.5 pb-2.5 ring-inset has-checked:bg-brand-50/50 dark:has-checked:bg-brand-950/40 has-checked:ring-2 has-checked:ring-brand-500 dark:has-checked:ring-brand-400 has-focus-visible:outline-2 has-focus-visible:outline-brand-600 dark:has-focus-visible:outline-brand-400"
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
                    </template>

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

                <section v-if="groupCollection.can.delete" :class="sectionClass" aria-labelledby="deletion-heading">
                    <div :class="listPanelHeaderClass">
                        <h2 id="deletion-heading" :class="headingClass">{{ $t('groupCollections.edit.sections.deletion') }}</h2>
                    </div>
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $t('deletion.warning.groupCollection') }}</p>
                    <div class="mt-4">
                        <DeleteButton kind="group-collections" :id="groupCollection.id" />
                    </div>
                </section>
            </template>
        </template>
    </AppLayout>
</template>
