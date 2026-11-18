<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { fetchUser, fetchUserGroupCollections, fetchUserWordGroups } from '@/api/users';
import AppLayout from '@/components/AppLayout.vue';
import CardListSection from '@/components/CardListSection.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePaginatedList } from '@/composables/usePaginatedList';
import type { User } from '@/types/models';

/** Another user's page: their groups and collections visible to the current user (public and shared). */
const route = useRoute();

const userId = computed(() => String(route.params.id));

const user = ref<Pick<User, 'id' | 'name'> | null>(null);
const status = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');

async function loadUser(): Promise<void> {
    status.value = 'loading';
    try {
        user.value = await fetchUser(userId.value);
        status.value = 'loaded';
    } catch (error) {
        status.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'failed';
    }
}

const wordGroups = usePaginatedList((page) => fetchUserWordGroups(userId.value, page), 'groups_page', [userId]);
const groupCollections = usePaginatedList(
    (page) => fetchUserGroupCollections(userId.value, page),
    'collections_page',
    [userId],
);

watch(userId, loadUser, { immediate: true });
</script>

<template>
    <AppLayout>
        <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
            ← Retour au tableau de bord
        </RouterLink>

        <p v-if="status === 'loading'" class="mt-4 text-zinc-500">Chargement…</p>

        <div v-else-if="status === 'not-found'" class="mt-4">
            <h1 class="text-2xl font-semibold">Utilisateur introuvable</h1>
        </div>

        <p v-else-if="status === 'failed'" class="mt-4 text-red-600">
            Impossible de charger cet utilisateur.
            <button type="button" class="underline" @click="loadUser">Réessayer</button>
        </p>

        <template v-else-if="user">
            <h1 class="mt-2 text-2xl font-semibold">{{ user.name }}</h1>
            <p class="mt-1 text-sm text-zinc-500">Ses groupes et collections publics, et ceux qu'il ou elle partage avec vous.</p>

            <div class="mt-8 space-y-12">
                <CardListSection
                    section-id="user-word-groups"
                    title="Groupes"
                    :result="wordGroups.result.value"
                    :is-loading="wordGroups.isLoading.value"
                    :has-failed="wordGroups.hasFailed.value"
                    empty-message="Aucun groupe visible."
                    paginated
                    page-param="groups_page"
                    @retry="wordGroups.reload"
                >
                    <template #item="{ item }">
                        <WordGroupCard :word-group="item" />
                    </template>
                </CardListSection>

                <CardListSection
                    section-id="user-group-collections"
                    title="Collections"
                    :result="groupCollections.result.value"
                    :is-loading="groupCollections.isLoading.value"
                    :has-failed="groupCollections.hasFailed.value"
                    empty-message="Aucune collection visible."
                    paginated
                    page-param="collections_page"
                    @retry="groupCollections.reload"
                >
                    <template #item="{ item }">
                        <GroupCollectionCard :group-collection="item" />
                    </template>
                </CardListSection>
            </div>
        </template>
    </AppLayout>
</template>
