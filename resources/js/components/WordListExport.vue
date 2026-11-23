<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { ExportedWord } from '@/api/exports';
import { useToasts } from '@/composables/useToasts';
import { intlLocale } from '@/lib/i18n';
import {
    SCORE_THRESHOLD_MAX,
    SKRIBBL_MAX_CHARACTERS,
    SKRIBBL_MIN_WORDS,
    deduplicateWords,
    drawWordsWithinLimit,
    excludeLowScoredWords,
    formatSkribblList,
    shuffleWords,
} from '@/lib/skribbl';
import { useAuthStore } from '@/stores/auth';

/**
 * Words formatted for the "Custom words" field of a private Skribbl.io room, with copy, counters,
 * limit warnings and list options (words scored too low, duplicates, shuffle, random selection within
 * the limit). Each occurrence of a word is judged on its own score before duplicates are removed.
 * Visitors see the score and duplicate options, unchecked, but only get a notification asking to log in.
 */
const props = withDefaults(
    defineProps<{
        /** Duplicates kept: the options deal with them. */
        words: ExportedWord[];
        /** Extra information, e.g. groups left out of the export. */
        note?: string | null;
        /** The words are being reloaded: the copy button waits. */
        isLoading?: boolean;
    }>(),
    { note: null, isLoading: false },
);

const { showToast } = useToasts();
const { t } = useI18n();
const auth = useAuthStore();
const LOCKED_OPTION_TOAST_MS = 5000;

const shouldExcludeLowScored = ref(false);
/** Words whose score is at or below it are left out. */
const scoreThreshold = ref(SCORE_THRESHOLD_MAX);
/** On by default for logged-in users; visitors cannot use it. */
const shouldDeduplicate = ref(auth.isVerified);
const shouldShuffle = ref(false);
const shuffledWords = ref<string[]>([]);
/** Random selection fitting in the character limit, when the user asked for one. */
const drawnWords = ref<string[] | null>(null);

const keptNames = computed(() =>
    (shouldExcludeLowScored.value ? excludeLowScoredWords(props.words, scoreThreshold.value) : props.words).map((word) => word.name),
);
const excludedCount = computed(() => props.words.length - keptNames.value.length);
const availableWords = computed(() => (shouldDeduplicate.value ? deduplicateWords(keptNames.value) : keptNames.value));
const exportedWords = computed(
    () => drawnWords.value ?? (shouldShuffle.value ? shuffledWords.value : availableWords.value),
);
const exportedList = computed(() => formatSkribblList(exportedWords.value));
const duplicatesCount = computed(() => keptNames.value.length - deduplicateWords(keptNames.value).length);

const hasTooFewWords = computed(() => exportedWords.value.length < SKRIBBL_MIN_WORDS);
const isOverCharacterLimit = computed(() => exportedList.value.length > SKRIBBL_MAX_CHARACTERS);

const numberFormat = computed(() => new Intl.NumberFormat(intlLocale()));

/** The option stays visible to visitors, but only says that logging in is needed (the header has the links). */
function onLockedOptionClick(event: Event): void {
    if (!auth.isVerified) {
        event.preventDefault();
        showToast(t('guest.lockedOption'), 'info', LOCKED_OPTION_TOAST_MS);
    }
}

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
        showToast(t('exports.list.copied'));
    } catch {
        showToast(t('exports.list.copyFailed'), 'error', 6000);
    }
}

const checkboxClass = 'size-4 rounded border-zinc-300 dark:border-zinc-700';
const stepButtonClass =
    'inline-flex size-6 items-center justify-center rounded-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm leading-none hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:cursor-default disabled:opacity-40 focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400';
const secondaryButtonClass =
    'rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60';
</script>

<template>
    <div>
        <label for="export-list" class="block text-sm font-medium">{{ $t('exports.list.label') }}</label>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $t('exports.list.help') }}
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
                {{ $t('exports.list.wordCount', { count: numberFormat.format(exportedWords.length) }, exportedWords.length) }} ·
                <span :class="{ 'font-medium text-red-600 dark:text-red-400': isOverCharacterLimit }">
                    {{
                        $t('exports.list.characterCount', {
                            count: numberFormat.format(exportedList.length),
                            max: numberFormat.format(SKRIBBL_MAX_CHARACTERS),
                        })
                    }}
                </span>
            </p>
            <button
                type="button"
                :disabled="isLoading"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                @click="copy"
            >
                {{ $t('common.copy') }}
            </button>
        </div>

        <div v-if="hasTooFewWords" role="note" class="mt-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-800 dark:text-amber-200">
            {{ $t('exports.list.tooFewWords', { min: SKRIBBL_MIN_WORDS }) }}
        </div>

        <div
            v-if="isOverCharacterLimit"
            role="note"
            class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-800 dark:text-amber-200"
        >
            <p>{{ $t('exports.list.tooManyCharacters', { max: numberFormat.format(SKRIBBL_MAX_CHARACTERS) }) }}</p>
            <button
                type="button"
                class="rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40"
                @click="draw"
            >
                {{ $t('exports.list.draw') }}
            </button>
        </div>

        <div
            v-if="drawnWords"
            class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800 px-3 py-2 text-sm"
        >
            <p>
                {{
                    $t('exports.list.drawn', {
                        count: numberFormat.format(drawnWords.length),
                        total: numberFormat.format(availableWords.length),
                    })
                }}
            </p>
            <div class="flex gap-2">
                <button type="button" :class="secondaryButtonClass" @click="draw">{{ $t('exports.list.drawAgain') }}</button>
                <button type="button" :class="secondaryButtonClass" @click="drawnWords = null">
                    {{ $t('exports.list.undraw') }}
                </button>
            </div>
        </div>

        <fieldset class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <legend class="sr-only">{{ $t('exports.list.options') }}</legend>
            <div class="inline-flex flex-wrap items-center gap-x-3 gap-y-1">
                <label class="inline-flex items-center gap-2">
                    <input v-model="shouldExcludeLowScored" type="checkbox" :class="checkboxClass" @click="onLockedOptionClick" />
                    {{ $t('exports.list.excludeLowScored') }}
                </label>
                <span v-if="shouldExcludeLowScored" role="group" :aria-label="$t('exports.list.thresholdLabel')" class="inline-flex items-center gap-1.5">
                    <button
                        type="button"
                        :class="stepButtonClass"
                        :aria-label="$t('exports.list.lowerThreshold')"
                        @click="scoreThreshold--"
                    >
                        −
                    </button>
                    <span class="tabular-nums" aria-live="polite">
                        {{ $t('exports.list.threshold', { threshold: numberFormat.format(scoreThreshold) }) }}
                    </span>
                    <button
                        type="button"
                        :class="stepButtonClass"
                        :aria-label="$t('exports.list.raiseThreshold')"
                        :disabled="scoreThreshold >= SCORE_THRESHOLD_MAX"
                        @click="scoreThreshold++"
                    >
                        +
                    </button>
                    <span class="text-zinc-500 dark:text-zinc-400">({{ $t('exports.list.excludedCount', excludedCount) }})</span>
                </span>
            </div>
            <label class="inline-flex items-center gap-2">
                <input v-model="shouldDeduplicate" type="checkbox" :class="checkboxClass" @click="onLockedOptionClick" />
                {{ $t('exports.list.deduplicate') }}
                <span v-if="duplicatesCount > 0" class="text-zinc-500 dark:text-zinc-400">({{ duplicatesCount }})</span>
            </label>
            <label class="inline-flex items-center gap-2" :class="{ 'opacity-60': drawnWords }">
                <input v-model="shouldShuffle" type="checkbox" :class="checkboxClass" :disabled="drawnWords !== null" />
                {{ $t('exports.list.shuffle') }}
            </label>
            <button
                v-if="shouldShuffle && !drawnWords"
                type="button"
                class="text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100"
                @click="reshuffle"
            >
                {{ $t('exports.list.reshuffle') }}
            </button>
        </fieldset>
    </div>
</template>
