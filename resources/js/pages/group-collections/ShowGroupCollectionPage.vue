<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { fetchGroupCollection } from '@/api/groupCollections';
import AppIcon from '@/components/AppIcon.vue';
import AppLayout from '@/components/AppLayout.vue';
import DuplicateButton from '@/components/DuplicateButton.vue';
import ExportPanel from '@/components/ExportPanel.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemHeader from '@/components/ItemHeader.vue';
import LeaveButton from '@/components/LeaveButton.vue';
import PendingAccessRequests from '@/components/PendingAccessRequests.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { useBreadcrumb } from '@/lib/breadcrumb';
import { secondaryButtonClass } from '@/lib/buttons';
import { cardGridItemClass } from '@/lib/cards';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import { listPanelClass, listPanelHeaderClass } from '@/lib/sections';
import { useAuthStore } from '@/stores/auth';
import type { GroupCollectionDetail } from '@/types/models';

/**
 * Page of a collection, made for reading: its title, the pending access requests (creator only), the export, the actions
 * that do not change it (duplicate, leave) and the groups the user can see (nothing tells that others are hidden).
 * Changing it happens on its edit page
 * (`EditGroupCollectionPage`), reached through "Gérer" (creator) or "Contribuer" (contributors).
 */
const route = useRoute();
const auth = useAuthStore();

/** Visitors (and unverified users, treated the same way by the API) can neither contribute nor request access. */
const isMember = computed(() => auth.isVerified);

const groupCollectionId = computed(() => String(route.params.id));

const groupCollection = ref<GroupCollectionDetail | null>(null);
const status = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
usePageTitle(() => groupCollection.value?.name);
useBreadcrumb(() => groupCollection.value?.name);

/** Whether the user can change something: everything for the creator, the groups for a contributor. */
const canEdit = computed(
    () => groupCollection.value !== null && (groupCollection.value.can.update || groupCollection.value.can.add_word_group),
);

/** An empty collection has nothing to copy. */
const canDuplicate = computed(() => (groupCollection.value?.can.duplicate ?? false) && (groupCollection.value?.word_groups.length ?? 0) > 0);

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
            <ItemHeader
                class="mt-2"
                :name="groupCollection.name"
                :language="groupCollection.language"
                :visibility="groupCollection.visibility"
                :accepts-contributions="groupCollection.accepts_contributions"
                :contributions-members-only="groupCollection.contributions_members_only"
                kind="group-collections"
                :creator="groupCollection.creator"
                :updated-at="groupCollection.updated_at"
            >
                <template v-if="groupCollection.is_favorite !== undefined" #after-title>
                    <FavoriteButton
                        v-model="groupCollection.is_favorite"
                        kind="group-collections"
                        :id="groupCollection.id"
                        :name="groupCollection.name"
                    />
                </template>
            </ItemHeader>

            <PendingAccessRequests v-if="groupCollection.can.manage_access" class="mt-4" kind="group-collections" :id="groupCollection.id" />

            <ExportPanel class="mt-4" kind="group-collections" :id="groupCollection.id" :locked="!isMember">
                <template #actions>
                    <RouterLink
                        v-if="canEdit"
                        :to="{ name: 'group-collections.edit', params: { id: groupCollection.id } }"
                        :class="secondaryButtonClass"
                    >
                        <AppIcon :name="groupCollection.can.update ? 'manage' : 'contribute'" />
                        {{ groupCollection.can.update ? $t('groupCollections.show.manage') : $t('groupCollections.show.contribute') }}
                    </RouterLink>
                    <DuplicateButton v-if="canDuplicate" kind="group-collections" :id="groupCollection.id" />
                    <LeaveButton
                        v-if="groupCollection.can.leave"
                        kind="group-collections"
                        :id="groupCollection.id"
                        :visibility="groupCollection.visibility"
                       
                        @left="onLeft"
                    />
                </template>
            </ExportPanel>

            <section class="mt-8" :class="listPanelClass" aria-labelledby="collection-groups-title">
                <div :class="listPanelHeaderClass">
                    <h2 id="collection-groups-title" class="text-lg font-semibold">
                        {{ $t('common.wordGroups') }} <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ groupCollection.word_groups.length }})</span>
                    </h2>
                </div>

                <template v-if="!groupCollection.can.add_word_group">
                    <!-- Plain text like the other contribution messages: the header has the links to log in and register. -->
                    <p v-if="!isMember && groupCollection.accepts_contributions" class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ auth.isAuthenticated
                            ? $t('guest.verifyEmail')
                            : $t(restrictsContributionsToMembers(groupCollection) ? 'guest.contributeMembersOnly.groupCollection' : 'guest.contribute.groupCollection') }}
                    </p>
                    <p v-else-if="restrictsContributionsToMembers(groupCollection)" class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $t('groupCollections.show.membersOnly') }}
                    </p>
                    <p v-else class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $t('groupCollections.show.closed') }}
                    </p>
                </template>

                <p
                    v-if="groupCollection.word_groups.length === 0"
                    class="mt-4 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center text-zinc-500 dark:text-zinc-400"
                >
                    {{ $t('groupCollections.show.empty') }}
                </p>

                <ul v-if="groupCollection.word_groups.length > 0" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="wordGroup in groupCollection.word_groups" :key="wordGroup.id" :class="cardGridItemClass">
                        <WordGroupCard :word-group="wordGroup" />
                    </li>
                </ul>
            </section>
        </template>
    </AppLayout>
</template>
