<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { fetchUser, fetchUserGroupCollections, fetchUserWordGroups } from '@/api/users';
import AppLayout from '@/components/AppLayout.vue';
import CardListSection from '@/components/CardListSection.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePaginatedList } from '@/composables/usePaginatedList';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types/models';

/**
 * A user's page: their groups and collections visible to the current user (public and shared). On their own
 * page, users see what the others see: their public items only.
 */
const route = useRoute();
const auth = useAuthStore();

const userId = computed(() => String(route.params.id));
const isOwnPage = computed(() => userId.value === auth.user?.id);

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
        <p v-if="status === 'loading'" class="mt-4 text-zinc-500 dark:text-zinc-400">{{ $t('common.loading') }}</p>

        <div v-else-if="status === 'not-found'" class="mt-4">
            <h1 class="text-2xl font-semibold">{{ $t('users.notFound') }}</h1>
        </div>

        <p v-else-if="status === 'failed'" class="mt-4 text-red-600 dark:text-red-400">
            {{ $t('users.loadFailed') }}
            <button type="button" class="underline" @click="loadUser">{{ $t('common.retry') }}</button>
        </p>

        <template v-else-if="user">
            <h1 class="mt-2 text-2xl font-semibold">{{ user.name }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ isOwnPage ? $t('users.ownDescription') : $t('users.description') }}</p>

            <div class="mt-8 space-y-12">
                <CardListSection
                    section-id="user-word-groups"
                    :title="$t('common.wordGroups')"
                    :result="wordGroups.result.value"
                    :is-loading="wordGroups.isLoading.value"
                    :has-failed="wordGroups.hasFailed.value"
                    :empty-message="$t('users.wordGroups.empty')"
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
                    :title="$t('common.groupCollections')"
                    :result="groupCollections.result.value"
                    :is-loading="groupCollections.isLoading.value"
                    :has-failed="groupCollections.hasFailed.value"
                    :empty-message="$t('users.groupCollections.empty')"
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
