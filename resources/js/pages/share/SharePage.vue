<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchShareInvitation, requestAccess } from '@/api/sharing';
import AppLayout from '@/components/AppLayout.vue';
import type { FavoriteKind, ShareInvitation } from '@/types/models';

/**
 * Page opened from a share link: request access to a private group or collection, or ask to contribute
 * to a public one open to its members only.
 */
const route = useRoute();
const router = useRouter();

const kind = computed<FavoriteKind>(() => (route.params.type === 'collections' ? 'group-collections' : 'word-groups'));
const token = computed(() => String(route.params.token));

const invitation = ref<ShareInvitation | null>(null);
const isContribution = computed(() => invitation.value?.purpose === 'contribution');
const itemLabel = computed(() => {
    if (kind.value === 'word-groups') {
        return isContribution.value ? 'le groupe' : 'le groupe privé';
    }

    return isContribution.value ? 'la collection' : 'la collection privée';
});
const itemRoute = computed(() =>
    invitation.value?.id
        ? {
              name: invitation.value.kind === 'word-groups' ? 'word-groups.show' : 'group-collections.show',
              params: { id: invitation.value.id },
          }
        : null,
);
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
            <p v-if="status === 'loading'" class="text-zinc-500 dark:text-zinc-400">Chargement…</p>

            <div v-else-if="status === 'not-found'" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                <h1 class="text-xl font-semibold">Lien invalide</h1>
                <p class="mt-2 text-zinc-500 dark:text-zinc-400">Ce lien de partage n'existe pas ou n'est plus valable. Demandez-en un nouveau.</p>
            </div>

            <p v-else-if="status === 'failed'" class="text-red-600 dark:text-red-400">
                Impossible de charger le partage.
                <button type="button" class="underline" @click="load">Réessayer</button>
            </p>

            <div v-else-if="invitation" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ invitation.creator.name }} partage {{ itemLabel }}</p>
                <h1 class="mt-1 text-2xl font-semibold">{{ invitation.name }}</h1>
                <p v-if="isContribution" class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Tout le monde peut le consulter, mais seules les personnes autorisées par
                    {{ invitation.creator.name }} peuvent y contribuer.
                    <RouterLink v-if="itemRoute" :to="itemRoute" class="font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">Voir le contenu</RouterLink>
                </p>

                <div class="mt-6">
                    <template v-if="invitation.status === 'pending'">
                        <p role="status" class="rounded-lg bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                            {{ isContribution ? 'Demande de contribution envoyée.' : "Demande d'accès envoyée." }} Vous recevrez un email dès que {{ invitation.creator.name }} l'aura traitée.
                        </p>
                    </template>
                    <template v-else-if="invitation.status !== 'accessible'">
                        <p v-if="invitation.status === 'refused'" class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                            Votre précédente demande a été refusée. Vous pouvez en envoyer une nouvelle.
                        </p>
                        <button
                            type="button"
                            :disabled="isRequesting"
                            class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                            @click="sendRequest"
                        >
                            {{ isContribution ? 'Demander à contribuer' : "Envoyer une demande d'accès" }}
                        </button>
                        <p v-if="requestFailed" role="alert" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            Une erreur est survenue, veuillez réessayer.
                        </p>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
