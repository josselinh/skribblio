<script setup lang="ts">
import { ref } from 'vue';
import { fetchShareLink, regenerateShareLink } from '@/api/sharing';
import type { FavoriteKind } from '@/types/models';

/**
 * "Partager" button of a private group or collection, opening the access request link with
 * copy and regenerate actions.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const isOpen = ref(false);
const url = ref<string | null>(null);
const status = ref<string | null>(null);
const hasFailed = ref(false);
const isConfirmingRegeneration = ref(false);
const isBusy = ref(false);

async function run(action: () => Promise<void>): Promise<void> {
    isBusy.value = true;
    hasFailed.value = false;
    try {
        await action();
    } catch {
        hasFailed.value = true;
    } finally {
        isBusy.value = false;
    }
}

async function toggle(): Promise<void> {
    isOpen.value = !isOpen.value;
    if (isOpen.value && url.value === null) {
        await run(async () => {
            url.value = await fetchShareLink(props.kind, props.id);
        });
    }
}

async function copy(): Promise<void> {
    if (!url.value) {
        return;
    }
    try {
        await navigator.clipboard.writeText(url.value);
        status.value = 'Lien copié.';
    } catch {
        status.value = 'Copie impossible : sélectionnez le lien et copiez-le.';
    }
}

async function regenerate(): Promise<void> {
    isConfirmingRegeneration.value = false;
    await run(async () => {
        url.value = await regenerateShareLink(props.kind, props.id);
        status.value = 'Nouveau lien créé. L’ancien ne fonctionne plus.';
    });
}
</script>

<template>
    <div>
        <button
            type="button"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium hover:bg-zinc-50"
            :aria-expanded="isOpen"
            aria-controls="share-panel"
            @click="toggle"
        >
            Partager
        </button>

        <div v-if="isOpen" id="share-panel" class="mt-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <label for="share-url" class="block text-sm font-medium">Lien de demande d'accès</label>
            <p class="text-sm text-zinc-500">
                Les personnes qui ouvrent ce lien peuvent vous demander l'accès. Vous accepterez ou refuserez chaque demande.
            </p>
            <p v-if="hasFailed" role="alert" class="mt-2 text-sm text-red-600">Une erreur est survenue, veuillez réessayer.</p>
            <div class="mt-2 flex gap-2">
                <input
                    id="share-url"
                    type="text"
                    readonly
                    :value="url ?? 'Chargement…'"
                    class="block w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm"
                    @focus="($event.target as HTMLInputElement).select()"
                />
                <button
                    type="button"
                    :disabled="!url || isBusy"
                    class="shrink-0 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-60"
                    @click="copy"
                >
                    Copier
                </button>
            </div>
            <p v-if="status" role="status" class="mt-2 text-sm text-green-700">{{ status }}</p>
            <div class="mt-3 text-sm">
                <template v-if="isConfirmingRegeneration">
                    <span class="text-zinc-600">L'ancien lien ne fonctionnera plus. Continuer ?</span>
                    <button type="button" class="ml-3 text-zinc-500 hover:text-zinc-900" @click="isConfirmingRegeneration = false">
                        Annuler
                    </button>
                    <button type="button" class="ml-3 font-medium text-red-600 hover:underline" @click="regenerate">
                        Régénérer
                    </button>
                </template>
                <button
                    v-else
                    type="button"
                    :disabled="!url || isBusy"
                    class="text-zinc-600 underline hover:text-zinc-900 disabled:opacity-60"
                    @click="isConfirmingRegeneration = true"
                >
                    Régénérer le lien
                </button>
            </div>
        </div>
    </div>
</template>
