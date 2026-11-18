<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { attachWordGroup, detachWordGroup, fetchGroupCollection, updateGroupCollection } from '@/api/groupCollections';
import AppLayout from '@/components/AppLayout.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import EditableNameVisibility, { type NameVisibilityPayload } from '@/components/EditableNameVisibility.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import WordGroupSearch from '@/components/WordGroupSearch.vue';
import type { ValidationErrorResponse } from '@/types/api';
import type { GroupCollectionDetail, WordGroup } from '@/types/models';

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

const hiddenGroupsMessage = computed(() => {
    const count = groupCollection.value?.hidden_word_groups_count ?? 0;
    if (count === 0) {
        return null;
    }

    return count === 1
        ? '1 groupe de cette collection est privé et n’est pas visible pour vous.'
        : `${count} groupes de cette collection sont privés et ne sont pas visibles pour vous.`;
});

async function loadGroupCollection(): Promise<void> {
    try {
        groupCollection.value = await fetchGroupCollection(groupCollectionId.value);
        status.value = 'loaded';
    } catch (error) {
        status.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'failed';
    }
}

async function saveGroupCollection(payload: NameVisibilityPayload): Promise<void> {
    const updated = await updateGroupCollection(groupCollectionId.value, payload);
    if (groupCollection.value) {
        groupCollection.value = { ...groupCollection.value, ...updated };
    }
}

/** Runs a change on the groups of the collection, then reloads it; shows validation errors (422). */
async function changeGroups(change: () => Promise<void>): Promise<void> {
    isUpdatingGroups.value = true;
    groupsError.value = null;
    try {
        await change();
        await loadGroupCollection();
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
    return changeGroups(() => attachWordGroup(groupCollectionId.value, wordGroup.id));
}

function removeWordGroup(wordGroup: WordGroup): Promise<void> {
    return changeGroups(() => detachWordGroup(groupCollectionId.value, wordGroup.id));
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
        <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
            ← Retour au tableau de bord
        </RouterLink>

        <p v-if="status === 'loading'" class="mt-4 text-zinc-500">Chargement…</p>

        <div v-else-if="status === 'not-found'" class="mt-4">
            <h1 class="text-2xl font-semibold">Collection introuvable</h1>
            <p class="mt-2 text-zinc-500">Cette collection n'existe pas ou vous n'y avez pas accès.</p>
        </div>

        <p v-else-if="status === 'failed'" class="mt-4 text-red-600">
            Impossible de charger la collection.
            <button type="button" class="underline" @click="loadGroupCollection">Réessayer</button>
        </p>

        <template v-else-if="groupCollection">
            <div class="mt-2">
                <EditableNameVisibility
                    :name="groupCollection.name"
                    :visibility="groupCollection.visibility"
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
                </EditableNameVisibility>
            </div>

            <p
                v-if="hiddenGroupsMessage"
                role="note"
                class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
            >
                {{ hiddenGroupsMessage }}
            </p>

            <section class="mt-8" aria-labelledby="collection-groups-title">
                <h2 id="collection-groups-title" class="text-lg font-semibold">
                    Groupes <span class="font-normal text-zinc-500">({{ totalWordGroupsCount }})</span>
                </h2>

                <div v-if="groupCollection.can.update" class="mt-3">
                    <label for="add-word-group" class="sr-only">Ajouter un groupe</label>
                    <WordGroupSearch
                        input-id="add-word-group"
                        :excluded-ids="wordGroupIds"
                        :disabled="isUpdatingGroups"
                        @select="addWordGroup"
                    />
                </div>

                <p v-if="groupsError" role="alert" class="mt-3 text-sm text-red-600">{{ groupsError }}</p>

                <p
                    v-if="groupCollection.word_groups.length === 0"
                    class="mt-4 rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500"
                >
                    Aucun groupe visible dans cette collection.
                </p>

                <ul
                    v-else
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    :class="{ 'opacity-60': isUpdatingGroups }"
                >
                    <li v-for="wordGroup in groupCollection.word_groups" :key="wordGroup.id" class="relative">
                        <WordGroupCard :word-group="wordGroup" />
                        <button
                            v-if="groupCollection.can.update"
                            type="button"
                            class="absolute right-3 bottom-3 rounded-md px-2 py-1 text-xs text-zinc-500 hover:bg-zinc-100 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-zinc-500"
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
