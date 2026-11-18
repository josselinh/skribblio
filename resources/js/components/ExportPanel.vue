<script setup lang="ts">
import { ref } from 'vue';
import { fetchExportWords } from '@/api/exports';
import WordListExport from '@/components/WordListExport.vue';
import type { FavoriteKind } from '@/types/models';

/**
 * "Exporter pour Skribbl.io" button of a group or a collection, opening the list of its words
 * ready to paste into Skribbl.io.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    /** Extra information shown in the panel, e.g. groups of a collection left out of the export. */
    note?: string | null;
}>();

const isOpen = ref(false);
const isLoading = ref(false);
const hasFailed = ref(false);
const words = ref<string[]>([]);

async function load(): Promise<void> {
    isLoading.value = true;
    hasFailed.value = false;
    try {
        words.value = await fetchExportWords(props.kind, props.id);
    } catch {
        hasFailed.value = true;
    } finally {
        isLoading.value = false;
    }
}

/** Reloads the words on each opening so that the list includes the latest changes. */
async function toggle(): Promise<void> {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        await load();
    }
}
</script>

<template>
    <div>
        <button
            type="button"
            class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-700"
            :aria-expanded="isOpen"
            aria-controls="export-panel"
            @click="toggle"
        >
            Exporter pour Skribbl.io
        </button>

        <div v-if="isOpen" id="export-panel" class="mt-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p v-if="isLoading && words.length === 0" class="text-sm text-zinc-500">Chargement…</p>

            <p v-else-if="hasFailed" role="alert" class="text-sm text-red-600">
                Impossible de charger les mots.
                <button type="button" class="underline" @click="load">Réessayer</button>
            </p>

            <p v-else-if="words.length === 0" class="text-sm text-zinc-500">Aucun mot à exporter.</p>

            <WordListExport v-else :words="words" :note="note" :is-loading="isLoading" />
        </div>
    </div>
</template>
