<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { attachWordGroup, detachWordGroup, fetchGroupCollection, updateGroupCollection } from '@/api/groupCollections';
import { requestHiddenWordGroupsAccess } from '@/api/sharing';
import AppLayout from '@/components/AppLayout.vue';
import AccessManagement from '@/components/AccessManagement.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import DuplicateButton from '@/components/DuplicateButton.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import LeaveButton from '@/components/LeaveButton.vue';
import ShareButton from '@/components/ShareButton.vue';
import ExportPanel from '@/components/ExportPanel.vue';
import EditableNameVisibility, { type NameVisibilityPayload } from '@/components/EditableNameVisibility.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import WordGroupSearch from '@/components/WordGroupSearch.vue';
import { useToasts } from '@/composables/useToasts';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import type { ValidationErrorResponse } from '@/types/api';
import type { AccessPurpose, GroupCollectionDetail, WordGroup } from '@/types/models';

const route = useRoute();

const groupCollectionId = computed(() => String(route.params.id));

const groupCollection = ref<GroupCollectionDetail | null>(null);
const status = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
const isUpdatingGroups = ref(false);
const groupsError = ref<string | null>(null);

const wordGroupIds = computed(() => groupCollection.value?.word_groups.map((wordGroup) => wordGroup.id) ?? []);

/** Includes the groups hidden from the user: the collection must keep at least one group. */
const totalWordGroupsCount = computed(() =>
    groupCollection.value ? groupCollection.value.word_groups.length + groupCollection.value.hidden_word_groups_count : 0,
);

const accessPurpose = computed<AccessPurpose>(() =>
    groupCollection.value && restrictsContributionsToMembers(groupCollection.value) ? 'contribution' : 'access',
);

const hiddenGroupsMessage = computed(() => {
    const count = groupCollection.value?.hidden_word_groups_count ?? 0;
    if (count === 0) {
        return null;
    }

    return count === 1
        ? '1 groupe de cette collection est privé et n’est pas visible pour vous.'
        : `${count} groupes de cette collection sont privés et ne sont pas visibles pour vous.`;
});

const exportNote = computed(() =>
    hiddenGroupsMessage.value ? 'Les mots des groupes qui vous sont masqués ne sont pas exportés.' : null,
);

/** Whether some hidden groups were not requested yet. */
const canRequestHiddenGroups = computed(
    () =>
        groupCollection.value !== null &&
        groupCollection.value.hidden_word_groups_requested_count < groupCollection.value.hidden_word_groups_count,
);
const isRequestingHiddenGroups = ref(false);
const hiddenGroupsRequestFailed = ref(false);

async function requestHiddenGroups(): Promise<void> {
    isRequestingHiddenGroups.value = true;
    hiddenGroupsRequestFailed.value = false;
    try {
        await requestHiddenWordGroupsAccess(groupCollectionId.value);
        await loadGroupCollection();
    } catch {
        hiddenGroupsRequestFailed.value = true;
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

/** Runs a change on the groups of the collection, then reloads it and confirms it; shows validation errors (422). */
async function changeGroups(change: () => Promise<void>, successMessage: string): Promise<void> {
    isUpdatingGroups.value = true;
    groupsError.value = null;
    try {
        await change();
        await loadGroupCollection();
        showToast(successMessage);
    } catch (error) {
        if (isAxiosError<ValidationErrorResponse>(error) && error.response?.status === 422) {
            groupsError.value = error.response.data.message;
        } else {
            groupsError.value = 'Une erreur est survenue, veuillez réessayer.';
        }
    } finally {
        isUpdatingGroups.value = false;
    }
}

function addWordGroup(wordGroup: WordGroup): Promise<void> {
    return changeGroups(
        () => attachWordGroup(groupCollectionId.value, wordGroup.id),
        `Groupe « ${wordGroup.name} » ajouté à la collection.`,
    );
}

function removeWordGroup(wordGroup: WordGroup): Promise<void> {
    return changeGroups(
        () => detachWordGroup(groupCollectionId.value, wordGroup.id),
        `Groupe « ${wordGroup.name} » retiré de la collection.`,
    );
}

watch(
    groupCollectionId,
    () => {
        status.value = 'loading';
        void loadGroupCollection();
    },
    { immediate: true },
);
</script>

<template>
    <AppLayout>

        <p v-if="status === 'loading'" class="mt-4 text-zinc-500 dark:text-zinc-400">Chargement…</p>

        <div v-else-if="status === 'not-found'" class="mt-4">
            <h1 class="text-2xl font-semibold">Collection introuvable</h1>
            <p class="mt-2 text-zinc-500 dark:text-zinc-400">Cette collection n'existe pas ou vous n'y avez pas accès.</p>
        </div>

        <p v-else-if="status === 'failed'" class="mt-4 text-red-600 dark:text-red-400">
            Impossible de charger la collection.
            <button type="button" class="underline" @click="loadGroupCollection">Réessayer</button>
        </p>

        <template v-else-if="groupCollection">
            <div class="mt-2">
                <EditableNameVisibility
                    :name="groupCollection.name"
                    :visibility="groupCollection.visibility"
                    :accepts-contributions="groupCollection.accepts_contributions"
                    :contributions-members-only="groupCollection.contributions_members_only"
                    kind="group-collections"
                    :creator="groupCollection.creator"
                    :can-edit="groupCollection.can.update"
                    name-label="Nom de la collection"
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

            <ExportPanel class="mt-4" kind="group-collections" :id="groupCollection.id" :note="exportNote" />

            <div
                v-if="hiddenGroupsMessage"
                role="note"
                class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-800 dark:text-amber-200"
            >
                <p>{{ hiddenGroupsMessage }}</p>
                <button
                    v-if="canRequestHiddenGroups"
                    type="button"
                    :disabled="isRequestingHiddenGroups"
                    class="rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-zinc-900 px-3 py-1.5 font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40 disabled:opacity-60"
                    @click="requestHiddenGroups"
                >
                    Demander l'accès aux groupes privés
                </button>
                <p v-else class="font-medium">Demande d'accès envoyée.</p>
                <p v-if="hiddenGroupsRequestFailed" role="alert" class="w-full text-red-600 dark:text-red-400">
                    Une erreur est survenue, veuillez réessayer.
                </p>
            </div>

            <AccessManagement
                v-if="groupCollection.can.share"
                class="mt-8"
                kind="group-collections"
                :id="groupCollection.id"
                :purpose="accessPurpose"
            />

            <section class="mt-8" aria-labelledby="collection-groups-title">
                <h2 id="collection-groups-title" class="text-lg font-semibold">
                    Groupes <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ totalWordGroupsCount }})</span>
                </h2>

                <div v-if="groupCollection.can.add_word_group" class="mt-3">
                    <label for="add-word-group" class="sr-only">Ajouter un groupe</label>
                    <WordGroupSearch
                        input-id="add-word-group"
                        :excluded-ids="wordGroupIds"
                        :disabled="isUpdatingGroups"
                        @select="addWordGroup"
                    />
                </div>

                <p v-else-if="restrictsContributionsToMembers(groupCollection)" class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    Seules les personnes autorisées par le créateur de cette collection peuvent y ajouter des groupes.
                    Demandez-lui son lien de partage pour lui proposer de contribuer.
                </p>
                <p v-else class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    Cette collection n’est pas ouverte aux contributions : seul son créateur peut modifier ses groupes.
                </p>

                <p v-if="groupsError" role="alert" class="mt-3 text-sm text-red-600 dark:text-red-400">{{ groupsError }}</p>

                <p
                    v-if="groupCollection.word_groups.length === 0"
                    class="mt-4 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-zinc-500 dark:text-zinc-400"
                >
                    {{
                        totalWordGroupsCount === 0
                            ? 'Cette collection ne contient plus aucun groupe : ceux qu’elle contenait ont été supprimés.'
                            : 'Aucun groupe visible dans cette collection.'
                    }}
                </p>

                <ul
                    v-else
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    :class="{ 'opacity-60': isUpdatingGroups }"
                >
                    <li v-for="wordGroup in groupCollection.word_groups" :key="wordGroup.id" class="relative">
                        <WordGroupCard :word-group="wordGroup" />
                        <button
                            v-if="wordGroup.can.remove_from_collection"
                            type="button"
                            class="absolute right-3 bottom-3 rounded-md px-2 py-1 text-xs text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-red-600 dark:hover:text-red-400 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-zinc-500 dark:disabled:hover:text-zinc-400"
                            :disabled="isUpdatingGroups || totalWordGroupsCount === 1"
                            :title="totalWordGroupsCount === 1 ? 'Une collection doit contenir au moins un groupe.' : undefined"
                            :aria-label="`Retirer ${wordGroup.name} de la collection`"
                            @click="removeWordGroup(wordGroup)"
                        >
                            Retirer
                        </button>
                    </li>
                </ul>
            </section>
        </template>
    </AppLayout>
</template>
