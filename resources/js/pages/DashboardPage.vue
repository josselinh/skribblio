<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { fetchFavoriteGroupCollections, fetchFavoriteWordGroups } from '@/api/favorites';
import { fetchMyGroupCollections } from '@/api/groupCollections';
import { fetchReceivedAccessRequests, fetchSharedGroupCollections, fetchSharedWordGroups } from '@/api/sharing';
import { fetchMyWordGroups } from '@/api/wordGroups';
import AccessRequestItem from '@/components/AccessRequestItem.vue';
import AppLayout from '@/components/AppLayout.vue';
import CardListSection from '@/components/CardListSection.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePaginatedList } from '@/composables/usePaginatedList';
import { consumeHistoryStatus } from '@/lib/historyStatus';
import type { AccessRequest, FavoriteKind } from '@/types/models';

const route = useRoute();

const favoriteWordGroups = usePaginatedList(fetchFavoriteWordGroups, 'favorite_groups_page');
const favoriteGroupCollections = usePaginatedList(fetchFavoriteGroupCollections, 'favorite_collections_page');
const wordGroups = usePaginatedList(fetchMyWordGroups, 'groups_page');
const groupCollections = usePaginatedList(fetchMyGroupCollections, 'collections_page');
const sharedWordGroups = usePaginatedList(fetchSharedWordGroups, 'shared_groups_page');
const sharedGroupCollections = usePaginatedList(fetchSharedGroupCollections, 'shared_collections_page');

/** Pending requests on the user's groups and collections; the section only shows when there are some. */
const receivedAccessRequests = ref<AccessRequest[]>([]);

async function loadReceivedAccessRequests(): Promise<void> {
    try {
        receivedAccessRequests.value = await fetchReceivedAccessRequests();
    } catch {
        receivedAccessRequests.value = [];
    }
}

function onAccessRequestDecided(decided: AccessRequest): void {
    receivedAccessRequests.value = receivedAccessRequests.value.filter((accessRequest) => accessRequest.id !== decided.id);
}

void loadReceivedAccessRequests();

/**
 * Keeps the star of the same item in sync across lists, and reloads the favorites list
 * so that the item appears in or disappears from it.
 */
async function onFavoriteSaved(kind: FavoriteKind, id: string, isFavorite: boolean): Promise<void> {
    const lists =
        kind === 'word-groups'
            ? [wordGroups, favoriteWordGroups, sharedWordGroups]
            : [groupCollections, favoriteGroupCollections, sharedGroupCollections];
    for (const list of lists) {
        list.result.value?.data.filter((item) => item.id === id).forEach((item) => (item.is_favorite = isFavorite));
    }
    await (kind === 'word-groups' ? favoriteWordGroups : favoriteGroupCollections).reload();
}

const flashStatus = consumeHistoryStatus();

const status = computed<string | null>(() =>
    route.query.verified === '1' ? 'Votre adresse email est confirmée, bienvenue !' : flashStatus,
);

/** A user with no group and no favorite yet is invited to explore what others share. */
const isNewcomer = computed(() =>
    [wordGroups, favoriteWordGroups, favoriteGroupCollections].every((list) => list.result.value?.meta.total === 0),
);

const buttonClass =
    'inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700';
const emptyFavoritesMessage = 'Ajoutez-en avec l’étoile ☆ pour les retrouver ici.';
</script>

<template>
    <AppLayout>
        <h1 class="sr-only">Tableau de bord</h1>
        <StatusMessage :message="status" />

        <div class="space-y-12">
            <div
                class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-xs"
            >
                <div>
                    <p class="font-semibold">Exporter pour Skribbl.io</p>
                    <p class="text-sm text-zinc-500">
                        Réunissez les mots de plusieurs groupes et collections en une liste prête à coller.
                    </p>
                </div>
                <RouterLink :to="{ name: 'exports.create' }" :class="buttonClass">Créer un export</RouterLink>
            </div>

            <div
                v-if="isNewcomer"
                class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-dashed border-zinc-300 p-5"
            >
                <div>
                    <p class="font-semibold">Nouveau sur Skribblio Tools ?</p>
                    <p class="text-sm text-zinc-500">
                        Parcourez les groupes et collections partagés par les autres utilisateurs et ajoutez vos préférés
                        en favoris.
                    </p>
                </div>
                <RouterLink :to="{ name: 'search' }" class="text-sm font-medium underline">
                    Découvrir les groupes de la communauté →
                </RouterLink>
            </div>

            <section v-if="receivedAccessRequests.length > 0" aria-labelledby="received-access-requests-title">
                <h2 id="received-access-requests-title" class="text-lg font-semibold">
                    Demandes d'accès reçues
                    <span class="font-normal text-zinc-500">({{ receivedAccessRequests.length }})</span>
                </h2>
                <ul class="mt-4 divide-y divide-zinc-100 rounded-xl border border-zinc-200 bg-white shadow-xs">
                    <AccessRequestItem
                        v-for="accessRequest in receivedAccessRequests"
                        :key="accessRequest.id"
                        :access-request="accessRequest"
                        show-item
                        @decided="onAccessRequestDecided"
                    />
                </ul>
            </section>

            <CardListSection
                section-id="favorite-word-groups"
                title="Mes groupes favoris"
                :result="favoriteWordGroups.result.value"
                :is-loading="favoriteWordGroups.isLoading.value"
                :has-failed="favoriteWordGroups.hasFailed.value"
                :empty-message="`Aucun groupe favori. ${emptyFavoritesMessage}`"
                paginated
                page-param="favorite_groups_page"
                @retry="favoriteWordGroups.reload"
            >
                <template #item="{ item }">
                    <WordGroupCard :word-group="item" @favorite-saved="onFavoriteSaved('word-groups', item.id, $event)" />
                </template>
            </CardListSection>

            <CardListSection
                section-id="favorite-group-collections"
                title="Mes collections favorites"
                :result="favoriteGroupCollections.result.value"
                :is-loading="favoriteGroupCollections.isLoading.value"
                :has-failed="favoriteGroupCollections.hasFailed.value"
                :empty-message="`Aucune collection favorite. ${emptyFavoritesMessage}`"
                paginated
                page-param="favorite_collections_page"
                @retry="favoriteGroupCollections.reload"
            >
                <template #item="{ item }">
                    <GroupCollectionCard
                        :group-collection="item"
                        @favorite-saved="onFavoriteSaved('group-collections', item.id, $event)"
                    />
                </template>
            </CardListSection>

            <CardListSection
                section-id="word-groups"
                title="Mes groupes"
                :result="wordGroups.result.value"
                :is-loading="wordGroups.isLoading.value"
                :has-failed="wordGroups.hasFailed.value"
                paginated
                page-param="groups_page"
                @retry="wordGroups.reload"
            >
                <template #actions>
                    <RouterLink :to="{ name: 'word-groups.create' }" :class="buttonClass">+ Nouveau groupe</RouterLink>
                </template>
                <template #empty>
                    <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center">
                        <p class="text-zinc-500">Vous n'avez encore créé aucun groupe.</p>
                        <RouterLink
                            :to="{ name: 'word-groups.create' }"
                            class="mt-2 inline-block text-sm font-medium underline"
                        >
                            Créer mon premier groupe
                        </RouterLink>
                    </div>
                </template>
                <template #item="{ item }">
                    <WordGroupCard :word-group="item" @favorite-saved="onFavoriteSaved('word-groups', item.id, $event)" />
                </template>
            </CardListSection>

            <CardListSection
                section-id="group-collections"
                title="Mes collections"
                :result="groupCollections.result.value"
                :is-loading="groupCollections.isLoading.value"
                :has-failed="groupCollections.hasFailed.value"
                paginated
                page-param="collections_page"
                @retry="groupCollections.reload"
            >
                <template #actions>
                    <RouterLink :to="{ name: 'group-collections.create' }" :class="buttonClass">
                        + Nouvelle collection
                    </RouterLink>
                </template>
                <template #empty>
                    <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center">
                        <p class="text-zinc-500">Vous n'avez encore créé aucune collection.</p>
                        <RouterLink
                            :to="{ name: 'group-collections.create' }"
                            class="mt-2 inline-block text-sm font-medium underline"
                        >
                            Créer ma première collection
                        </RouterLink>
                    </div>
                </template>
                <template #item="{ item }">
                    <GroupCollectionCard
                        :group-collection="item"
                        @favorite-saved="onFavoriteSaved('group-collections', item.id, $event)"
                    />
                </template>
            </CardListSection>

            <CardListSection
                section-id="shared-word-groups"
                title="Groupes partagés avec moi"
                :result="sharedWordGroups.result.value"
                :is-loading="sharedWordGroups.isLoading.value"
                :has-failed="sharedWordGroups.hasFailed.value"
                empty-message="Aucun groupe privé partagé avec vous. Ouvrez un lien de partage pour demander l’accès."
                paginated
                page-param="shared_groups_page"
                @retry="sharedWordGroups.reload"
            >
                <template #item="{ item }">
                    <WordGroupCard :word-group="item" @favorite-saved="onFavoriteSaved('word-groups', item.id, $event)" />
                </template>
            </CardListSection>

            <CardListSection
                section-id="shared-group-collections"
                title="Collections partagées avec moi"
                :result="sharedGroupCollections.result.value"
                :is-loading="sharedGroupCollections.isLoading.value"
                :has-failed="sharedGroupCollections.hasFailed.value"
                empty-message="Aucune collection privée partagée avec vous. Ouvrez un lien de partage pour demander l’accès."
                paginated
                page-param="shared_collections_page"
                @retry="sharedGroupCollections.reload"
            >
                <template #item="{ item }">
                    <GroupCollectionCard
                        :group-collection="item"
                        @favorite-saved="onFavoriteSaved('group-collections', item.id, $event)"
                    />
                </template>
            </CardListSection>
        </div>
    </AppLayout>
</template>
