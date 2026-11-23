<script setup lang="ts">
import { computed, ref } from 'vue';
import { fetchGroupCollectionExport, fetchWordGroupExport, type ExportedWordGroup } from '@/api/exports';
import { useI18n } from 'vue-i18n';
import WordListExport from '@/components/WordListExport.vue';
import { useToasts } from '@/composables/useToasts';
import { intlLocale } from '@/lib/i18n';
import type { FavoriteKind } from '@/types/models';

/**
 * "Exporter pour Skribbl.io" button of a group or a collection, opening the list of its words
 * ready to paste into Skribbl.io. A collection's export comes with its groups (the visible ones),
 * so that the user picks which of them to export without reloading anything.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    /** Extra information shown in the panel, e.g. groups of a collection left out of the export. */
    note?: string | null;
    /**
     * Shows the button without opening anything: clicking it only says, in a notification, that logging in
     * is needed (visitors on a collection; the header has the links).
     */
    locked?: boolean;
}>();

const { t } = useI18n();
const { showToast } = useToasts();
const LOCKED_OPTION_TOAST_MS = 5000;

const isOpen = ref(false);
const isLoading = ref(false);
const hasFailed = ref(false);
/** Words of a group, or the groups of a collection with their words. */
const wordGroupWords = ref<string[]>([]);
const wordGroups = ref<ExportedWordGroup[]>([]);

/**
 * Groups the user unchecked, kept across openings. Keeping the excluded ones rather than the selected
 * ones means that a group added to the collection afterwards is exported by default.
 */
const excludedIds = ref(new Set<string>());

const showsSelection = computed(() => props.kind === 'group-collections' && wordGroups.value.length > 1);
const selectedWordGroups = computed(() => wordGroups.value.filter((wordGroup) => !excludedIds.value.has(wordGroup.id)));
const isEverythingSelected = computed(() => selectedWordGroups.value.length === wordGroups.value.length);
const isNothingSelected = computed(() => showsSelection.value && selectedWordGroups.value.length === 0);

/** Words of the checked groups, sorted by name, duplicates kept (the export options deal with them). */
const words = computed(() =>
    props.kind === 'word-groups'
        ? wordGroupWords.value
        : selectedWordGroups.value.flatMap((wordGroup) => wordGroup.words).sort((a, b) => a.localeCompare(b, intlLocale)),
);

function toggleWordGroup(id: string, isSelected: boolean): void {
    const excluded = new Set(excludedIds.value);
    if (isSelected) {
        excluded.delete(id);
    } else {
        excluded.add(id);
    }
    excludedIds.value = excluded;
}

function selectAll(isSelected: boolean): void {
    excludedIds.value = new Set(isSelected ? [] : wordGroups.value.map((wordGroup) => wordGroup.id));
}

async function load(): Promise<void> {
    isLoading.value = true;
    hasFailed.value = false;
    try {
        if (props.kind === 'word-groups') {
            wordGroupWords.value = await fetchWordGroupExport(props.id);
        } else {
            wordGroups.value = (await fetchGroupCollectionExport(props.id)).word_groups;
        }
    } catch {
        hasFailed.value = true;
    } finally {
        isLoading.value = false;
    }
}

/** Reloads the words on each opening so that the list includes the latest changes. */
async function toggle(): Promise<void> {
    if (props.locked) {
        showToast(t('guest.lockedOption'), 'info', LOCKED_OPTION_TOAST_MS);

        return;
    }
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        await load();
    }
}

const linkButtonClass =
    'text-sm text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100 disabled:cursor-default disabled:no-underline disabled:opacity-50';
</script>

<template>
    <div>
        <button
            type="button"
            class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
            :aria-expanded="isOpen"
            aria-controls="export-panel"
            @click="toggle"
        >
            {{ $t('exports.panel.button') }}
        </button>

        <div v-if="isOpen" id="export-panel" class="mt-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm">
            <p v-if="isLoading && words.length === 0 && !isNothingSelected" class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('common.loading') }}</p>

            <p v-else-if="hasFailed" role="alert" class="text-sm text-red-600 dark:text-red-400">
                {{ $t('exports.panel.loadFailed') }}
                <button type="button" class="underline" @click="load">{{ $t('common.retry') }}</button>
            </p>

            <template v-else>
                <fieldset v-if="showsSelection" class="mb-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <legend class="text-sm font-medium">
                        {{ $t('exports.panel.groups') }}
                        <span class="font-normal text-zinc-500 dark:text-zinc-400">{{ $t('exports.panel.selectedCount', { selected: selectedWordGroups.length, total: wordGroups.length }) }}</span>
                    </legend>
                    <div class="mt-1 flex gap-3">
                        <button type="button" :class="linkButtonClass" :disabled="isEverythingSelected" @click="selectAll(true)">
                            {{ $t('exports.panel.selectAll') }}
                        </button>
                        <button type="button" :class="linkButtonClass" :disabled="isNothingSelected" @click="selectAll(false)">
                            {{ $t('exports.panel.selectNone') }}
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <label
                            v-for="wordGroup in wordGroups"
                            :key="wordGroup.id"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1 text-sm has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
                        >
                            <input
                                type="checkbox"
                                :checked="!excludedIds.has(wordGroup.id)"
                                @change="toggleWordGroup(wordGroup.id, ($event.target as HTMLInputElement).checked)"
                            />
                            {{ wordGroup.name }}
                            <span class="text-zinc-500 dark:text-zinc-400">
                                · {{ $t('words.count', wordGroup.words.length) }}
                            </span>
                        </label>
                    </div>
                </fieldset>

                <p v-if="isNothingSelected" class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('exports.panel.nothingSelected') }}</p>

                <p v-else-if="words.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('exports.panel.noWords') }}</p>

                <WordListExport v-else :words="words" :note="note" :is-loading="isLoading" />
            </template>
        </div>
    </div>
</template>
