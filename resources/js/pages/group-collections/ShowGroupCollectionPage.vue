<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { fetchGroupCollection } from '@/api/groupCollections';
import { requestHiddenWordGroupsAccess } from '@/api/sharing';
import AppLayout from '@/components/AppLayout.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DuplicateButton from '@/components/DuplicateButton.vue';
import ExportPanel from '@/components/ExportPanel.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemHeader from '@/components/ItemHeader.vue';
import LeaveButton from '@/components/LeaveButton.vue';
import PendingAccessRequests from '@/components/PendingAccessRequests.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { useToasts } from '@/composables/useToasts';
import { secondaryButtonClass } from '@/lib/buttons';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import { useAuthStore } from '@/stores/auth';
import type { GroupCollectionDetail } from '@/types/models';

/**
 * Page of a collection, made for reading: its title, the pending access requests (creator only), the export, the actions that do not change it (duplicate, leave),
 * the groups hidden from the user (with an access request) and its groups. Changing it happens on its edit page
 * (`EditGroupCollectionPage`), reached through "Gérer" (creator) or "Contribuer" (contributors).
 */
const route = useRoute();
const { t } = useI18n();
const { showToast } = useToasts();
const auth = useAuthStore();

/** Visitors (and unverified users, treated the same way by the API) can neither contribute nor request access. */
const isMember = computed(() => auth.isVerified);

const groupCollectionId = computed(() => String(route.params.id));

const groupCollection = ref<GroupCollectionDetail | null>(null);
const status = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
usePageTitle(() => groupCollection.value?.name);

/** Whether the user can change something: everything for the creator, the groups for a contributor. */
const canEdit = computed(
    () => groupCollection.value !== null && (groupCollection.value.can.update || groupCollection.value.can.add_word_group),
);

/** Includes the groups hidden from the user. */
const totalWordGroupsCount = computed(() =>
    groupCollection.value ? groupCollection.value.word_groups.length + groupCollection.value.hidden_word_groups_count : 0,
);

/** An empty collection has nothing to copy. */
const canDuplicate = computed(() => (groupCollection.value?.can.duplicate ?? false) && (groupCollection.value?.word_groups.length ?? 0) > 0);

const hiddenGroupsMessage = computed(() => {
    const count = groupCollection.value?.hidden_word_groups_count ?? 0;

    return count === 0 ? null : t('groupCollections.show.hidden.message', count);
});

const exportNote = computed(() =>
    hiddenGroupsMessage.value ? t('groupCollections.show.hidden.exportNote') : null,
);

/** Whether some hidden groups were not requested yet. */
const canRequestHiddenGroups = computed(
    () =>
        groupCollection.value !== null &&
        groupCollection.value.hidden_word_groups_requested_count < groupCollection.value.hidden_word_groups_count,
);
const isRequestingHiddenGroups = ref(false);
const hiddenGroupsRequestError = ref<string | null>(null);
const isConfirmingHiddenGroupsRequest = ref(false);

/** Hidden groups not requested yet, which the request will be sent for. */
const unrequestedHiddenGroupsCount = computed(() =>
    groupCollection.value ? groupCollection.value.hidden_word_groups_count - groupCollection.value.hidden_word_groups_requested_count : 0,
);

function askHiddenGroupsRequestConfirmation(): void {
    hiddenGroupsRequestError.value = null;
    isConfirmingHiddenGroupsRequest.value = true;
}

/** Sends the confirmed request, then reloads the collection; errors stay in the dialog. */
async function requestHiddenGroups(): Promise<void> {
    isRequestingHiddenGroups.value = true;
    hiddenGroupsRequestError.value = null;
    try {
        await requestHiddenWordGroupsAccess(groupCollectionId.value);
        isConfirmingHiddenGroupsRequest.value = false;
        await loadGroupCollection();
        showToast(t('groupCollections.show.hidden.confirmation.done'));
    } catch {
        hiddenGroupsRequestError.value = t('common.unexpectedError');
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

            <ExportPanel class="mt-4" kind="group-collections" :id="groupCollection.id" :note="exportNote" :locked="!isMember">
                <template #actions>
                    <RouterLink
                        v-if="canEdit"
                        :to="{ name: 'group-collections.edit', params: { id: groupCollection.id } }"
                        :class="secondaryButtonClass"
                    >
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

            <div
                v-if="hiddenGroupsMessage"
                role="note"
                class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-800 dark:text-amber-200"
            >
                <p>{{ hiddenGroupsMessage }}</p>
                <button
                    v-if="isMember && canRequestHiddenGroups"
                    type="button"
                    :disabled="isRequestingHiddenGroups"
                    class="rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-zinc-900 px-3 py-1.5 font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40 disabled:opacity-60"
                    aria-haspopup="dialog"
                    @click="askHiddenGroupsRequestConfirmation"
                >
                    {{ $t('groupCollections.show.hidden.request') }}
                </button>
                <p v-else-if="isMember" class="font-medium">{{ $t('groupCollections.show.hidden.requested') }}</p>
            </div>
            <ConfirmDialog
                v-model:open="isConfirmingHiddenGroupsRequest"
                :title="$t('groupCollections.show.hidden.confirmation.title')"
                :message="$t('groupCollections.show.hidden.confirmation.message', unrequestedHiddenGroupsCount)"
                :confirm-label="$t('groupCollections.show.hidden.confirmation.confirm')"
                :processing-label="$t('groupCollections.show.hidden.confirmation.processing')"
                tone="primary"
                :is-processing="isRequestingHiddenGroups"
                :error="hiddenGroupsRequestError"
                @confirm="requestHiddenGroups"
            />

            <section class="mt-8" aria-labelledby="collection-groups-title">
                <h2 id="collection-groups-title" class="text-lg font-semibold">
                    {{ $t('common.wordGroups') }} <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ totalWordGroupsCount }})</span>
                </h2>

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
                    {{ totalWordGroupsCount === 0 ? $t('groupCollections.show.empty') : $t('groupCollections.show.noVisibleGroup') }}
                </p>

                <ul v-if="groupCollection.word_groups.length > 0" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="wordGroup in groupCollection.word_groups" :key="wordGroup.id">
                        <WordGroupCard :word-group="wordGroup" />
                    </li>
                </ul>
            </section>
        </template>
    </AppLayout>
</template>
