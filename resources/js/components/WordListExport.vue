<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useToasts } from '@/composables/useToasts';
import {
    SKRIBBL_MAX_CHARACTERS,
    SKRIBBL_MIN_WORDS,
    deduplicateWords,
    drawWordsWithinLimit,
    formatSkribblList,
    shuffleWords,
} from '@/lib/skribbl';

/**
 * Words formatted for the "Custom words" field of a private Skribbl.io room, with copy, counters,
 * limit warnings and list options (duplicates, shuffle, random selection within the limit).
 */
const props = withDefaults(
    defineProps<{
        words: string[];
        /** Extra information, e.g. groups left out of the export. */
        note?: string | null;
        /** The words are being reloaded: the copy button waits. */
        isLoading?: boolean;
    }>(),
    { note: null, isLoading: false },
);

const { showToast } = useToasts();

const shouldDeduplicate = ref(true);
const shouldShuffle = ref(false);
const shuffledWords = ref<string[]>([]);
/** Random selection fitting in the character limit, when the user asked for one. */
const drawnWords = ref<string[] | null>(null);

const availableWords = computed(() => (shouldDeduplicate.value ? deduplicateWords(props.words) : props.words));
const exportedWords = computed(
    () => drawnWords.value ?? (shouldShuffle.value ? shuffledWords.value : availableWords.value),
);
const exportedList = computed(() => formatSkribblList(exportedWords.value));
const duplicatesCount = computed(() => props.words.length - deduplicateWords(props.words).length);

const hasTooFewWords = computed(() => exportedWords.value.length < SKRIBBL_MIN_WORDS);
const isOverCharacterLimit = computed(() => exportedList.value.length > SKRIBBL_MAX_CHARACTERS);

const numberFormat = new Intl.NumberFormat('fr-FR');

function reshuffle(): void {
    shuffledWords.value = shuffleWords(availableWords.value);
}

function draw(): void {
    drawnWords.value = drawWordsWithinLimit(availableWords.value);
}

/** Any change of the source list cancels the random selection and refreshes the shuffled order. */
watch(
    availableWords,
    () => {
        drawnWords.value = null;
        reshuffle();
    },
    { immediate: true },
);

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(exportedList.value);
        showToast('Liste copiée. Collez-la dans le champ « Custom words » de votre salon Skribbl.io.');
    } catch {
        showToast('Copie impossible : sélectionnez la liste et copiez-la.', 'error', 6000);
    }
}

const checkboxClass = 'size-4 rounded border-zinc-300 dark:border-zinc-700';
const secondaryButtonClass =
    'rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60';
</script>

<template>
    <div>
        <label for="export-list" class="block text-sm font-medium">Mots personnalisés</label>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            Copiez cette liste dans le champ « Custom words » d'un salon privé Skribbl.io.
        </p>
        <p v-if="note" class="mt-1 text-sm text-amber-700 dark:text-amber-400">{{ note }}</p>

        <textarea
            id="export-list"
            readonly
            rows="6"
            :value="exportedList"
            class="mt-2 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 font-mono text-sm"
            :class="{ 'opacity-60': isLoading }"
            @focus="($event.target as HTMLTextAreaElement).select()"
        ></textarea>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ numberFormat.format(exportedWords.length) }} mot{{ exportedWords.length > 1 ? 's' : '' }} ·
                <span :class="{ 'font-medium text-red-600 dark:text-red-400': isOverCharacterLimit }">
                    {{ numberFormat.format(exportedList.length) }} / {{ numberFormat.format(SKRIBBL_MAX_CHARACTERS) }}
                    caractères
                </span>
            </p>
            <button
                type="button"
                :disabled="isLoading"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                @click="copy"
            >
                Copier
            </button>
        </div>

        <div v-if="hasTooFewWords" role="note" class="mt-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-800 dark:text-amber-200">
            Skribbl.io demande au moins {{ SKRIBBL_MIN_WORDS }} mots personnalisés.
        </div>

        <div
            v-if="isOverCharacterLimit"
            role="note"
            class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-800 dark:text-amber-200"
        >
            <p>
                La liste dépasse la limite de {{ numberFormat.format(SKRIBBL_MAX_CHARACTERS) }} caractères de
                Skribbl.io.
            </p>
            <button
                type="button"
                class="rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40"
                @click="draw"
            >
                Tirer au hasard une sélection qui tient dans la limite
            </button>
        </div>

        <div
            v-if="drawnWords"
            class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm"
        >
            <p>
                Sélection aléatoire de {{ numberFormat.format(drawnWords.length) }} mots sur
                {{ numberFormat.format(availableWords.length) }}.
            </p>
            <div class="flex gap-2">
                <button type="button" :class="secondaryButtonClass" @click="draw">Nouveau tirage</button>
                <button type="button" :class="secondaryButtonClass" @click="drawnWords = null">
                    Revenir à la liste complète
                </button>
            </div>
        </div>

        <fieldset class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <legend class="sr-only">Options de la liste</legend>
            <label class="inline-flex items-center gap-2">
                <input v-model="shouldDeduplicate" type="checkbox" :class="checkboxClass" />
                Retirer les doublons
                <span v-if="duplicatesCount > 0" class="text-zinc-500 dark:text-zinc-400">({{ duplicatesCount }})</span>
            </label>
            <label class="inline-flex items-center gap-2" :class="{ 'opacity-60': drawnWords }">
                <input v-model="shouldShuffle" type="checkbox" :class="checkboxClass" :disabled="drawnWords !== null" />
                Mélanger l'ordre
            </label>
            <button
                v-if="shouldShuffle && !drawnWords"
                type="button"
                class="text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100"
                @click="reshuffle"
            >
                Remélanger
            </button>
        </fieldset>
    </div>
</template>
