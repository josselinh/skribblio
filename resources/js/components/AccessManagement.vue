<script setup lang="ts">
import { ref, watch } from 'vue';
import { fetchItemAccess, revokeAccess } from '@/api/sharing';
import AccessRequestItem from '@/components/AccessRequestItem.vue';
import { formatDateTime } from '@/lib/format';
import type { FavoriteKind, ItemAccess } from '@/types/models';

/** Pending access requests and people with access of a private item, for its creator. */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const access = ref<ItemAccess | null>(null);
const hasFailed = ref(false);
const memberBeingRevoked = ref<string | null>(null);

async function load(): Promise<void> {
    hasFailed.value = false;
    try {
        access.value = await fetchItemAccess(props.kind, props.id);
    } catch {
        hasFailed.value = true;
    }
}

async function revoke(userId: string): Promise<void> {
    try {
        await revokeAccess(props.kind, props.id, userId);
    } finally {
        memberBeingRevoked.value = null;
        await load();
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
                    Demandes d'accès <span class="font-normal text-zinc-500">({{ access.pending_requests.length }})</span>
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
                    Personnes ayant accès <span class="font-normal text-zinc-500">({{ access.members.length }})</span>
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
                        <div v-if="memberBeingRevoked === member.id" class="flex gap-3">
                            <span class="text-zinc-500">Retirer l'accès ?</span>
                            <button type="button" class="text-zinc-500 hover:text-zinc-900" @click="memberBeingRevoked = null">Non</button>
                            <button type="button" class="font-medium text-red-600 hover:underline" @click="revoke(member.id)">Oui</button>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="text-red-600 hover:text-red-800"
                            @click="memberBeingRevoked = member.id"
                        >
                            Retirer
                        </button>
                    </li>
                </ul>
            </div>
        </template>
    </section>
</template>
