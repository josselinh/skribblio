<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { fetchMyGroupCollections } from '@/api/groupCollections';
import { fetchMyWordGroups } from '@/api/wordGroups';
import AppLayout from '@/components/AppLayout.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePaginatedList } from '@/composables/usePaginatedList';

const route = useRoute();

const wordGroups = usePaginatedList(fetchMyWordGroups, 'groups_page');
const groupCollections = usePaginatedList(fetchMyGroupCollections, 'collections_page');

/**
 * Status sent by the previous page through the history state (e.g. after creating a collection),
 * read once then removed so that it does not show up again on reload.
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
</script>

<template>
    <AppLayout>
        <h1 class="sr-only">Tableau de bord</h1>
        <StatusMessage :message="status" />

        <div class="space-y-12">
            <section aria-labelledby="word-groups-title">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 id="word-groups-title" class="text-2xl font-semibold">Mes groupes</h2>
                    <RouterLink :to="{ name: 'word-groups.create' }" :class="buttonClass">+ Nouveau groupe</RouterLink>
                </div>

                <p v-if="wordGroups.isLoading.value && !wordGroups.result.value" class="mt-6 text-zinc-500">Chargement…</p>
                <p v-else-if="wordGroups.hasFailed.value" class="mt-6 text-red-600">
                    Impossible de charger vos groupes.
                    <button type="button" class="underline" @click="wordGroups.reload">Réessayer</button>
                </p>
                <div
                    v-else-if="wordGroups.result.value?.data.length === 0"
                    class="mt-6 rounded-xl border border-dashed border-zinc-300 p-8 text-center"
                >
                    <p class="text-zinc-500">Vous n'avez encore créé aucun groupe.</p>
                    <RouterLink :to="{ name: 'word-groups.create' }" class="mt-2 inline-block text-sm font-medium underline">
                        Créer mon premier groupe
                    </RouterLink>
                </div>
                <template v-else-if="wordGroups.result.value">
                    <ul
                        class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        :class="{ 'opacity-60': wordGroups.isLoading.value }"
                    >
                        <li v-for="wordGroup in wordGroups.result.value.data" :key="wordGroup.id">
                            <WordGroupCard :word-group="wordGroup" />
                        </li>
                    </ul>
                    <PaginationNav
                        class="mt-6"
                        page-param="groups_page"
                        :current-page="wordGroups.result.value.meta.current_page"
                        :last-page="wordGroups.result.value.meta.last_page"
                    />
                </template>
            </section>

            <section aria-labelledby="group-collections-title">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 id="group-collections-title" class="text-2xl font-semibold">Mes collections</h2>
                    <RouterLink :to="{ name: 'group-collections.create' }" :class="buttonClass">
                        + Nouvelle collection
                    </RouterLink>
                </div>

                <p v-if="groupCollections.isLoading.value && !groupCollections.result.value" class="mt-6 text-zinc-500">
                    Chargement…
                </p>
                <p v-else-if="groupCollections.hasFailed.value" class="mt-6 text-red-600">
                    Impossible de charger vos collections.
                    <button type="button" class="underline" @click="groupCollections.reload">Réessayer</button>
                </p>
                <div
                    v-else-if="groupCollections.result.value?.data.length === 0"
                    class="mt-6 rounded-xl border border-dashed border-zinc-300 p-8 text-center"
                >
                    <p class="text-zinc-500">Vous n'avez encore créé aucune collection.</p>
                    <RouterLink
                        :to="{ name: 'group-collections.create' }"
                        class="mt-2 inline-block text-sm font-medium underline"
                    >
                        Créer ma première collection
                    </RouterLink>
                </div>
                <template v-else-if="groupCollections.result.value">
                    <ul
                        class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        :class="{ 'opacity-60': groupCollections.isLoading.value }"
                    >
                        <li v-for="groupCollection in groupCollections.result.value.data" :key="groupCollection.id">
                            <GroupCollectionCard :group-collection="groupCollection" />
                        </li>
                    </ul>
                    <PaginationNav
                        class="mt-6"
                        page-param="collections_page"
                        :current-page="groupCollections.result.value.meta.current_page"
                        :last-page="groupCollections.result.value.meta.last_page"
                    />
                </template>
            </section>
        </div>
    </AppLayout>
</template>
