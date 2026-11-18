<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchShareInvitation, requestAccess } from '@/api/sharing';
import AppLayout from '@/components/AppLayout.vue';
import type { FavoriteKind, ShareInvitation } from '@/types/models';

/** Page opened from a share link: request access to a private group or collection. */
const route = useRoute();
const router = useRouter();

const kind = computed<FavoriteKind>(() => (route.params.type === 'collections' ? 'group-collections' : 'word-groups'));
const token = computed(() => String(route.params.token));
const itemLabel = computed(() => (kind.value === 'word-groups' ? 'le groupe privé' : 'la collection privée'));

const invitation = ref<ShareInvitation | null>(null);
const status = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');
const isRequesting = ref(false);
const requestFailed = ref(false);

/** When the user already has access, opens the item directly. */
async function show(loaded: ShareInvitation): Promise<void> {
    invitation.value = loaded;
    status.value = 'loaded';
    if (loaded.status === 'accessible' && loaded.id) {
        await router.replace({
            name: loaded.kind === 'word-groups' ? 'word-groups.show' : 'group-collections.show',
            params: { id: loaded.id },
        });
    }
}

async function load(): Promise<void> {
    status.value = 'loading';
    try {
        await show(await fetchShareInvitation(kind.value, token.value));
    } catch (error) {
        status.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'failed';
    }
}

async function sendRequest(): Promise<void> {
    isRequesting.value = true;
    requestFailed.value = false;
    try {
        await show(await requestAccess(kind.value, token.value));
    } catch {
        requestFailed.value = true;
    } finally {
        isRequesting.value = false;
    }
}

watch([kind, token], load, { immediate: true });
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <p v-if="status === 'loading'" class="text-zinc-500">Chargement…</p>

            <div v-else-if="status === 'not-found'" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h1 class="text-xl font-semibold">Lien invalide</h1>
                <p class="mt-2 text-zinc-500">Ce lien de partage n'existe pas ou n'est plus valable. Demandez-en un nouveau.</p>
            </div>

            <p v-else-if="status === 'failed'" class="text-red-600">
                Impossible de charger le partage.
                <button type="button" class="underline" @click="load">Réessayer</button>
            </p>

            <div v-else-if="invitation" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-zinc-500">{{ invitation.creator.name }} partage {{ itemLabel }}</p>
                <h1 class="mt-1 text-2xl font-semibold">{{ invitation.name }}</h1>

                <div class="mt-6">
                    <template v-if="invitation.status === 'pending'">
                        <p role="status" class="rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                            Demande d'accès envoyée. Vous recevrez un email dès que {{ invitation.creator.name }} l'aura traitée.
                        </p>
                    </template>
                    <template v-else-if="invitation.status !== 'accessible'">
                        <p v-if="invitation.status === 'refused'" class="mb-4 text-sm text-zinc-600">
                            Votre précédente demande a été refusée. Vous pouvez en envoyer une nouvelle.
                        </p>
                        <button
                            type="button"
                            :disabled="isRequesting"
                            class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-60"
                            @click="sendRequest"
                        >
                            Envoyer une demande d'accès
                        </button>
                        <p v-if="requestFailed" role="alert" class="mt-2 text-sm text-red-600">
                            Une erreur est survenue, veuillez réessayer.
                        </p>
                    </template>
                </div>

                <RouterLink :to="{ name: 'dashboard' }" class="mt-6 inline-block text-sm text-zinc-500 hover:text-zinc-900">
                    ← Retour au tableau de bord
                </RouterLink>
            </div>
        </div>
    </AppLayout>
</template>
