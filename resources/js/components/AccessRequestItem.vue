<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { decideAccessRequest } from '@/api/sharing';
import { formatDateTime } from '@/lib/format';
import type { AccessRequest } from '@/types/models';

/** One pending access request with its Accept and Refuse buttons. */
const props = withDefaults(
    defineProps<{
        accessRequest: AccessRequest;
        /** Shows which group or collection is requested (dashboard), not needed on the item's page. */
        showItem?: boolean;
    }>(),
    { showItem: false },
);

const emit = defineEmits<{
    decided: [accessRequest: AccessRequest, accepted: boolean];
}>();

const isDeciding = ref(false);
const hasFailed = ref(false);

async function decide(accept: boolean): Promise<void> {
    isDeciding.value = true;
    hasFailed.value = false;
    try {
        await decideAccessRequest(props.accessRequest, accept);
        emit('decided', props.accessRequest, accept);
    } catch {
        hasFailed.value = true;
    } finally {
        isDeciding.value = false;
    }
}
</script>

<template>
    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div class="min-w-0 text-sm">
            <p>
                <span class="font-medium">{{ accessRequest.requester.name }}</span>
                <template v-if="showItem">
                    demande l'accès {{ accessRequest.kind === 'word-groups' ? 'au groupe' : 'à la collection' }}
                    <RouterLink
                        :to="{
                            name: accessRequest.kind === 'word-groups' ? 'word-groups.show' : 'group-collections.show',
                            params: { id: accessRequest.item.id },
                        }"
                        class="font-medium underline"
                    >
                        {{ accessRequest.item.name }}
                    </RouterLink>
                </template>
                <template v-else> demande l'accès</template>
            </p>
            <p class="text-zinc-500">
                <template v-if="accessRequest.via_group_collection">
                    Depuis la collection « {{ accessRequest.via_group_collection.name }} » ·
                </template>
                <time v-if="accessRequest.created_at" :datetime="accessRequest.created_at">
                    {{ formatDateTime(accessRequest.created_at) }}
                </time>
            </p>
            <p v-if="hasFailed" role="alert" class="text-red-600">Une erreur est survenue, veuillez réessayer.</p>
        </div>
        <div class="flex gap-2">
            <button
                type="button"
                :disabled="isDeciding"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 disabled:opacity-60"
                @click="decide(false)"
            >
                Refuser
            </button>
            <button
                type="button"
                :disabled="isDeciding"
                class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-60"
                @click="decide(true)"
            >
                Accepter
            </button>
        </div>
    </li>
</template>
