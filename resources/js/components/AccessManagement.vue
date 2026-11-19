<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { fetchItemAccess, revokeAccess } from '@/api/sharing';
import AccessRequestItem from '@/components/AccessRequestItem.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { formatDateTime } from '@/lib/format';
import type { AccessPurpose, FavoriteKind, ItemAccess } from '@/types/models';

/**
 * Pending requests and members of a shared item, for its creator: people with access to a private item,
 * or allowed to contribute to a public item open to its members only (`purpose`).
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    purpose: AccessPurpose;
}>();

const access = ref<ItemAccess | null>(null);
const hasFailed = ref(false);
type Member = ItemAccess['members'][number];

/** Member whose access is being confirmed for removal, in `ConfirmDialog`. */
const memberBeingRevoked = ref<Member | null>(null);
const isRevoking = ref(false);
const revokeError = ref<string | null>(null);

const isConfirmingRevocation = computed({
    get: () => memberBeingRevoked.value !== null,
    set: (isOpen: boolean) => {
        if (!isOpen) {
            memberBeingRevoked.value = null;
        }
    },
});

const revocationMessage = computed(() => {
    const name = memberBeingRevoked.value?.name ?? '';
    const item = props.kind === 'word-groups' ? 'ce groupe' : 'cette collection';

    return props.purpose === 'contribution'
        ? `${name} ne pourra plus contribuer à ${item}. Ses contributions sont conservées, et cette personne pourra vous redemander l'autorisation.`
        : `${name} n'aura plus accès à ${item}. Cette personne pourra vous redemander l'accès avec le lien de partage.`;
});

function confirmRevocation(member: Member): void {
    revokeError.value = null;
    memberBeingRevoked.value = member;
}

async function load(): Promise<void> {
    hasFailed.value = false;
    try {
        access.value = await fetchItemAccess(props.kind, props.id);
    } catch {
        hasFailed.value = true;
    }
}

async function revoke(): Promise<void> {
    if (!memberBeingRevoked.value) {
        return;
    }
    isRevoking.value = true;
    revokeError.value = null;
    try {
        await revokeAccess(props.kind, props.id, memberBeingRevoked.value.id);
        memberBeingRevoked.value = null;
        await load();
    } catch {
        revokeError.value = 'Une erreur est survenue, veuillez réessayer.';
    } finally {
        isRevoking.value = false;
    }
}

watch(() => props.id, load, { immediate: true });
</script>

<template>
    <section class="space-y-6" aria-label="Accès">
        <p v-if="hasFailed" class="text-sm text-red-600">
            Impossible de charger les accès.
            <button type="button" class="underline" @click="load">Réessayer</button>
        </p>

        <template v-else-if="access">
            <div v-if="access.pending_requests.length > 0">
                <h2 class="text-lg font-semibold">
                    {{ purpose === 'contribution' ? 'Demandes de contribution' : "Demandes d'accès" }}
                    <span class="font-normal text-zinc-500">({{ access.pending_requests.length }})</span>
                </h2>
                <ul class="mt-3 divide-y divide-zinc-100 rounded-xl border border-zinc-200 bg-white shadow-xs">
                    <AccessRequestItem
                        v-for="accessRequest in access.pending_requests"
                        :key="accessRequest.id"
                        :access-request="accessRequest"
                        @decided="load"
                    />
                </ul>
            </div>

            <div>
                <h2 class="text-lg font-semibold">
                    {{ purpose === 'contribution' ? 'Personnes autorisées à contribuer' : 'Personnes ayant accès' }}
                    <span class="font-normal text-zinc-500">({{ access.members.length }})</span>
                </h2>
                <p v-if="access.members.length === 0" class="mt-2 text-sm text-zinc-500">
                    Personne d'autre que vous pour l'instant. Partagez le lien pour recevoir des demandes.
                </p>
                <ul v-else class="mt-3 divide-y divide-zinc-100 rounded-xl border border-zinc-200 bg-white shadow-xs">
                    <li v-for="member in access.members" :key="member.id" class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ member.name }}</p>
                            <p v-if="member.granted_at" class="text-zinc-500">
                                Depuis le <time :datetime="member.granted_at">{{ formatDateTime(member.granted_at) }}</time>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="text-red-600 hover:text-red-800"
                            aria-haspopup="dialog"
                            @click="confirmRevocation(member)"
                        >
                            Retirer
                        </button>
                    </li>
                </ul>
            </div>
        </template>

        <ConfirmDialog
            v-model:open="isConfirmingRevocation"
            :title="purpose === 'contribution' ? 'Retirer l’autorisation de contribuer ?' : 'Retirer l’accès ?'"
            :message="revocationMessage"
            confirm-label="Retirer"
            processing-label="Retrait…"
            :is-processing="isRevoking"
            :error="revokeError"
            @confirm="revoke"
        />
    </section>
</template>
