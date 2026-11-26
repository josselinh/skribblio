<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { fetchItemAccess } from '@/api/sharing';
import AccessRequestItem from '@/components/AccessRequestItem.vue';
import type { AccessRequest, FavoriteKind } from '@/types/models';

/**
 * Pending access (or contribution) requests of a group or collection, for its creator, on the item's page: shown
 * only when there are some, each with its Accept and Refuse buttons. The members are managed on the edit page
 * (`AccessManagement`). A loading failure shows nothing, as the page is about the item, not its sharing.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const pendingRequests = ref<AccessRequest[]>([]);

/** The heading follows the requests: access to a private item, contribution to a public one. */
const purpose = computed(() => pendingRequests.value[0]?.purpose ?? 'access');

async function load(): Promise<void> {
    try {
        pendingRequests.value = (await fetchItemAccess(props.kind, props.id)).pending_requests;
    } catch {
        pendingRequests.value = [];
    }
}

function onDecided(accessRequest: AccessRequest): void {
    pendingRequests.value = pendingRequests.value.filter((pendingRequest) => pendingRequest.id !== accessRequest.id);
}

watch(() => props.id, load, { immediate: true });
</script>

<template>
    <section v-if="pendingRequests.length > 0" aria-labelledby="pending-access-requests-title">
        <h2 id="pending-access-requests-title" class="text-lg font-semibold">
            {{ $t(`sharing.management.requests.${purpose}`) }}
            <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ pendingRequests.length }})</span>
        </h2>
        <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
            <AccessRequestItem
                v-for="accessRequest in pendingRequests"
                :key="accessRequest.id"
                :access-request="accessRequest"
                @decided="onDecided"
            />
        </ul>
    </section>
</template>
