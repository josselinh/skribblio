<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { fetchFavoriteGroupCollections, fetchFavoriteWordGroups } from '@/api/favorites';
import { fetchMyGroupCollections } from '@/api/groupCollections';
import { fetchMyWordGroups } from '@/api/wordGroups';
import AppLayout from '@/components/AppLayout.vue';
import CardListSection from '@/components/CardListSection.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePaginatedList } from '@/composables/usePaginatedList';
import type { FavoriteKind } from '@/types/models';

const route = useRoute();

const favoriteWordGroups = usePaginatedList(fetchFavoriteWordGroups, 'favorite_groups_page');
const favoriteGroupCollections = usePaginatedList(fetchFavoriteGroupCollections, 'favorite_collections_page');
const wordGroups = usePaginatedList(fetchMyWordGroups, 'groups_page');
const groupCollections = usePaginatedList(fetchMyGroupCollections, 'collections_page');

/**
 * Keeps the star of the same item in sync across lists, and reloads the favorites list
 * so that the item appears in or disappears from it.
 */
async function onFavoriteSaved(kind: FavoriteKind, id: string, isFavorite: boolean): Promise<void> {
    const lists = kind === 'word-groups' ? [wordGroups, favoriteWordGroups] : [groupCollections, favoriteGroupCollections];
    for (const list of lists) {
        list.result.value?.data.filter((item) => item.id === id).forEach((item) => (item.is_favorite = isFavorite));
    }
    await (kind === 'word-groups' ? favoriteWordGroups : favoriteGroupCollections).reload();
}

/**
 * Status sent by the previous page through the history state, read once then removed
 * so that it does not show up again on reload.
 */
function consumeHistoryStatus(): string | null {
    const { status: historyStatus, ...state } = (window.history.state ?? {}) as Record<string, unknown>;
    if (typeof historyStatus !== 'string') {
        return null;
    }
    window.history.replaceState(state, '');

    return historyStatus;
}

const flashStatus = consumeHistoryStatus();

const status = computed<string | null>(() =>
    route.query.verified === '1' ? 'Votre adresse email est confirmée, bienvenue !' : flashStatus,
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
        </div>
    </AppLayout>
</template>
