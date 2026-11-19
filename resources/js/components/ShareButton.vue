<script setup lang="ts">
import { ref } from 'vue';
import { fetchShareLink, regenerateShareLink } from '@/api/sharing';
import ModalDialog from '@/components/ModalDialog.vue';
import type { AccessPurpose, FavoriteKind } from '@/types/models';

/**
 * "Inviter" button of a private group or collection, or of a public one open to its members only, opening a
 * modal with the access (or contribution) request link and its copy and regenerate actions. The link is
 * loaded on the first opening.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    purpose: AccessPurpose;
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

async function open(): Promise<void> {
    status.value = null;
    isConfirmingRegeneration.value = false;
    isOpen.value = true;
    if (url.value === null) {
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
    <button
        type="button"
        class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800"
        aria-haspopup="dialog"
        @click="open"
    >
        Inviter
    </button>

    <ModalDialog
        v-model:open="isOpen"
        :title="purpose === 'contribution' ? 'Inviter des contributeurs' : 'Inviter des personnes'"
    >
        <p>
            {{
                purpose === 'contribution'
                    ? 'Les personnes qui ouvrent ce lien peuvent vous demander à contribuer. Vous accepterez ou refuserez chaque demande.'
                    : "Les personnes qui ouvrent ce lien peuvent vous demander l'accès. Vous accepterez ou refuserez chaque demande."
            }}
        </p>
        <p v-if="hasFailed" role="alert" class="mt-3 text-red-600 dark:text-red-400">Une erreur est survenue, veuillez réessayer.</p>

        <label for="share-url" class="mt-4 block font-medium text-zinc-900 dark:text-zinc-100">
            {{ purpose === 'contribution' ? 'Lien de demande de contribution' : "Lien de demande d'accès" }}
        </label>
        <div class="mt-1 flex flex-col gap-2 sm:flex-row">
            <input
                id="share-url"
                type="text"
                readonly
                :value="url ?? 'Chargement…'"
                class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100"
                @focus="($event.target as HTMLInputElement).select()"
            />
            <button
                type="button"
                autofocus
                :disabled="!url || isBusy"
                class="shrink-0 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60 sm:py-2"
                @click="copy"
            >
                Copier
            </button>
        </div>
        <p v-if="status" role="status" class="mt-2 text-green-700 dark:text-green-400">{{ status }}</p>

        <div
            v-if="isConfirmingRegeneration"
            class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-3 py-2"
        >
            <span class="text-red-800 dark:text-red-300">L'ancien lien ne fonctionnera plus. Continuer ?</span>
            <span class="flex gap-3">
                <button type="button" class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100" @click="isConfirmingRegeneration = false">
                    Annuler
                </button>
                <button type="button" class="font-medium text-red-600 dark:text-red-400 hover:underline" @click="regenerate">Régénérer</button>
            </span>
        </div>
        <button
            v-else
            type="button"
            :disabled="!url || isBusy"
            class="mt-4 text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100 disabled:opacity-60"
            @click="isConfirmingRegeneration = true"
        >
            Régénérer le lien
        </button>

        <template #actions>
            <button
                type="button"
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 sm:w-auto sm:py-2"
                @click="isOpen = false"
            >
                Fermer
            </button>
        </template>
    </ModalDialog>
</template>
