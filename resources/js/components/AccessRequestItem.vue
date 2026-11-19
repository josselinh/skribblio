<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { decideAccessRequest } from '@/api/sharing';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import UserLink from '@/components/UserLink.vue';
import { formatDateTime } from '@/lib/format';
import type { AccessRequest } from '@/types/models';

/** One pending access (or contribution) request with its Accept and Refuse buttons. */
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

const requestLabel = computed(() =>
    props.accessRequest.purpose === 'contribution' ? 'demande à contribuer' : "demande l'accès",
);

const isDeciding = ref(false);
const hasFailed = ref(false);
const isConfirmingAcceptance = ref(false);

const acceptanceMessage = computed(() => {
    const { requester, item, kind, purpose } = props.accessRequest;
    const target = `${kind === 'word-groups' ? 'au groupe' : 'à la collection'} « ${item.name} »`;

    return purpose === 'contribution'
        ? `${requester.name} pourra contribuer ${target}. Vous pourrez lui retirer cette autorisation à tout moment.`
        : `${requester.name} aura accès ${target} et pourra exporter ses mots. Vous pourrez lui retirer l'accès à tout moment.`;
});

async function decide(accept: boolean): Promise<void> {
    isDeciding.value = true;
    hasFailed.value = false;
    try {
        await decideAccessRequest(props.accessRequest, accept);
        isConfirmingAcceptance.value = false;
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
                <UserLink :user="accessRequest.requester" class="font-medium" />{{ ' ' }}
                <template v-if="showItem">
                    {{ requestLabel }} {{ accessRequest.kind === 'word-groups' ? 'au groupe' : 'à la collection' }}
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
                <template v-else>{{ requestLabel }}</template>
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
                aria-haspopup="dialog"
                @click="isConfirmingAcceptance = true"
            >
                Accepter
            </button>
        </div>

        <ConfirmDialog
            v-model:open="isConfirmingAcceptance"
            tone="primary"
            :title="accessRequest.purpose === 'contribution' ? 'Autoriser à contribuer ?' : 'Donner l’accès ?'"
            :message="acceptanceMessage"
            confirm-label="Accepter"
            processing-label="Acceptation…"
            :is-processing="isDeciding"
            :error="hasFailed ? 'Une erreur est survenue, veuillez réessayer.' : null"
            @confirm="decide(true)"
        />
    </li>
</template>
