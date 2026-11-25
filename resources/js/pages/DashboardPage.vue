<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchFavoriteGroupCollections, fetchFavoriteWordGroups } from '@/api/favorites';
import { fetchMyGroupCollections } from '@/api/groupCollections';
import { fetchReceivedAccessRequests, fetchSharedGroupCollections, fetchSharedWordGroups } from '@/api/sharing';
import { fetchMyWordGroups } from '@/api/wordGroups';
import AccessRequestItem from '@/components/AccessRequestItem.vue';
import AppLayout from '@/components/AppLayout.vue';
import CardListSection from '@/components/CardListSection.vue';
import GroupCollectionCard from '@/components/GroupCollectionCard.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePaginatedList } from '@/composables/usePaginatedList';
import { useToasts } from '@/composables/useToasts';
import type { AccessRequest, FavoriteKind } from '@/types/models';

const route = useRoute();
const router = useRouter();
const { showToast } = useToasts();
const { t } = useI18n();

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

/** Back from the email verification link: welcomes the user once, then drops the flag so a reload does not repeat it. */
onMounted(() => {
    if (route.query.verified !== '1') {
        return;
    }
    showToast(t('dashboard.emailVerified'));
    const { verified: _verified, ...query } = route.query;
    void router.replace({ query });
});

/**
 * Favorites and shared lists only show once loaded with at least one item (or to offer a retry); they come after
 * the user's own lists so that their appearance does not push the rest of the page down.
 */
function isShown(list: ReturnType<typeof usePaginatedList>): boolean {
    return list.hasFailed.value || (list.result.value?.meta.total ?? 0) > 0;
}

/**
 * A user with no group or collection yet, whether their own, a favorite or shared with them (pending requests
 * included), is invited to explore what others share. Hidden until every list has answered, so it never flashes.
 */
const isNewcomer = computed(() =>
    [
        wordGroups,
        groupCollections,
        favoriteWordGroups,
        favoriteGroupCollections,
        sharedWordGroups,
        sharedGroupCollections,
    ].every((list) => list.result.value?.meta.total === 0),
);

const buttonClass =
    'inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700';
</script>

<template>
    <AppLayout>
        <h1 class="sr-only">{{ $t('layout.header.dashboard') }}</h1>

        <div class="space-y-12">
            <div
                v-if="isNewcomer"
                class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-5"
            >
                <div>
                    <p class="font-semibold">{{ $t('dashboard.newcomer.title') }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $t('dashboard.newcomer.description') }}
                    </p>
                </div>
                <RouterLink :to="{ name: 'explore' }" class="text-sm font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">
                    {{ $t('dashboard.newcomer.explore') }}
                </RouterLink>
            </div>

            <section v-if="receivedAccessRequests.length > 0" aria-labelledby="received-access-requests-title">
                <h2 id="received-access-requests-title" class="text-lg font-semibold">
                    {{ $t('dashboard.receivedRequests') }}
                    <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ receivedAccessRequests.length }})</span>
                </h2>
                <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
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
                section-id="word-groups"
                :title="$t('dashboard.wordGroups.title')"
                :result="wordGroups.result.value"
                :is-loading="wordGroups.isLoading.value"
                :has-failed="wordGroups.hasFailed.value"
                paginated
                page-param="groups_page"
                @retry="wordGroups.reload"
            >
                <template #actions>
                    <RouterLink :to="{ name: 'word-groups.create' }" :class="buttonClass">{{ $t('dashboard.wordGroups.create') }}</RouterLink>
                </template>
                <template #empty>
                    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center">
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $t('dashboard.wordGroups.empty') }}</p>
                        <RouterLink
                            :to="{ name: 'word-groups.create' }"
                            class="mt-2 inline-block text-sm font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200"
                        >
                            {{ $t('dashboard.wordGroups.createFirst') }}
                        </RouterLink>
                    </div>
                </template>
                <template #item="{ item }">
                    <WordGroupCard :word-group="item" @favorite-saved="onFavoriteSaved('word-groups', item.id, $event)" />
                </template>
            </CardListSection>

            <CardListSection
                section-id="group-collections"
                :title="$t('dashboard.groupCollections.title')"
                :result="groupCollections.result.value"
                :is-loading="groupCollections.isLoading.value"
                :has-failed="groupCollections.hasFailed.value"
                paginated
                page-param="collections_page"
                @retry="groupCollections.reload"
            >
                <template #actions>
                    <RouterLink :to="{ name: 'group-collections.create' }" :class="buttonClass">
                        {{ $t('dashboard.groupCollections.create') }}
                    </RouterLink>
                </template>
                <template #empty>
                    <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center">
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $t('dashboard.groupCollections.empty') }}</p>
                        <RouterLink
                            :to="{ name: 'group-collections.create' }"
                            class="mt-2 inline-block text-sm font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200"
                        >
                            {{ $t('dashboard.groupCollections.createFirst') }}
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
                v-if="isShown(favoriteWordGroups)"
                section-id="favorite-word-groups"
                :title="$t('dashboard.favoriteWordGroups')"
                :result="favoriteWordGroups.result.value"
                :is-loading="favoriteWordGroups.isLoading.value"
                :has-failed="favoriteWordGroups.hasFailed.value"
                paginated
                page-param="favorite_groups_page"
                @retry="favoriteWordGroups.reload"
            >
                <template #item="{ item }">
                    <WordGroupCard :word-group="item" @favorite-saved="onFavoriteSaved('word-groups', item.id, $event)" />
                </template>
            </CardListSection>

            <CardListSection
                v-if="isShown(favoriteGroupCollections)"
                section-id="favorite-group-collections"
                :title="$t('dashboard.favoriteGroupCollections')"
                :result="favoriteGroupCollections.result.value"
                :is-loading="favoriteGroupCollections.isLoading.value"
                :has-failed="favoriteGroupCollections.hasFailed.value"
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
                v-if="isShown(sharedWordGroups)"
                section-id="shared-word-groups"
                :title="$t('dashboard.sharedWordGroups')"
                :result="sharedWordGroups.result.value"
                :is-loading="sharedWordGroups.isLoading.value"
                :has-failed="sharedWordGroups.hasFailed.value"
                paginated
                page-param="shared_groups_page"
                @retry="sharedWordGroups.reload"
            >
                <template #item="{ item }">
                    <WordGroupCard :word-group="item" @favorite-saved="onFavoriteSaved('word-groups', item.id, $event)" />
                </template>
            </CardListSection>

            <CardListSection
                v-if="isShown(sharedGroupCollections)"
                section-id="shared-group-collections"
                :title="$t('dashboard.sharedGroupCollections')"
                :result="sharedGroupCollections.result.value"
                :is-loading="sharedGroupCollections.isLoading.value"
                :has-failed="sharedGroupCollections.hasFailed.value"
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
