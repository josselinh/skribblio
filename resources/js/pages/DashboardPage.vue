<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { fetchMyWordGroups } from '@/api/wordGroups';
import AppLayout from '@/components/AppLayout.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import type { PaginatedResponse } from '@/types/api';
import type { WordGroup } from '@/types/models';

const route = useRoute();

const wordGroups = ref<PaginatedResponse<WordGroup> | null>(null);
const isLoading = ref(true);
const hasFailed = ref(false);

const currentPage = computed(() => {
    const page = Number(route.query.page);

    return Number.isInteger(page) && page > 0 ? page : 1;
});

/**
 * Status sent by the previous page through the history state (e.g. after creating a group),
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

async function loadWordGroups(page: number): Promise<void> {
    isLoading.value = true;
    hasFailed.value = false;
    try {
        wordGroups.value = await fetchMyWordGroups(page);
    } catch {
        hasFailed.value = true;
    } finally {
        isLoading.value = false;
    }
}

watch(currentPage, loadWordGroups, { immediate: true });
</script>

<template>
    <AppLayout>
        <StatusMessage :message="status" />

        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Mes groupes</h1>
            <RouterLink
                :to="{ name: 'word-groups.create' }"
                class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700"
            >
                + Nouveau groupe
            </RouterLink>
        </div>

        <p v-if="isLoading && !wordGroups" class="mt-8 text-zinc-500">Chargement…</p>

        <p v-else-if="hasFailed" class="mt-8 text-red-600">
            Impossible de charger vos groupes.
            <button type="button" class="underline" @click="loadWordGroups(currentPage)">Réessayer</button>
        </p>

        <div
            v-else-if="wordGroups && wordGroups.data.length === 0"
            class="mt-8 rounded-xl border border-dashed border-zinc-300 p-8 text-center"
        >
            <p class="text-zinc-500">Vous n'avez encore créé aucun groupe.</p>
            <RouterLink :to="{ name: 'word-groups.create' }" class="mt-2 inline-block text-sm font-medium underline">
                Créer mon premier groupe
            </RouterLink>
        </div>

        <template v-else-if="wordGroups">
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" :class="{ 'opacity-60': isLoading }">
                <li v-for="wordGroup in wordGroups.data" :key="wordGroup.id">
                    <WordGroupCard :word-group="wordGroup" />
                </li>
            </ul>
            <PaginationNav
                class="mt-6"
                :current-page="wordGroups.meta.current_page"
                :last-page="wordGroups.meta.last_page"
            />
        </template>
    </AppLayout>
</template>
