<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import { deleteWords, updateWords, type WordRename } from '@/api/words';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useToasts } from '@/composables/useToasts';
import { SKRIBBL_MAX_WORD_LENGTH } from '@/lib/skribbl';
import type { ValidationErrorResponse } from '@/types/api';
import type { Word } from '@/types/models';

/**
 * Edit mode of a page of words: every word the user may update becomes a text field, saved all at once
 * (only the changed ones are sent; they are highlighted, each with a "Rétablir" button, and leaving edit mode
 * with changes asks for a confirmation), and the words they may delete get a checkbox for a bulk deletion.
 * The parent reloads the words on `deleted` (edit mode stays on, unsaved names are kept) and leaves edit
 * mode on `saved` and `cancel`.
 */
const props = defineProps<{
    wordGroupId: string;
    words: Word[];
}>();

const emit = defineEmits<{
    saved: [];
    deleted: [deletedCount: number];
    cancel: [];
}>();

const { showToast } = useToasts();

/** Names being typed, by word id. */
const draftNames = reactive<Record<string, string>>({});
/** First validation error of each word, by word id. */
const nameErrors = ref<Record<string, string>>({});
const saveError = ref<string | null>(null);
const isSaving = ref(false);

const selectedWordIds = ref<string[]>([]);
const isConfirmingDelete = ref(false);
const isDeleting = ref(false);
const deleteError = ref<string | null>(null);

/** Keeps what was typed for the words still listed after a reload, and forgets the others. */
watch(
    () => props.words,
    (words) => {
        const ids = new Set(words.map((word) => word.id));
        for (const id of Object.keys(draftNames)) {
            if (!ids.has(id)) {
                delete draftNames[id];
            }
        }
        for (const word of words) {
            draftNames[word.id] ??= word.name;
        }
        selectedWordIds.value = selectedWordIds.value.filter((id) => ids.has(id));
    },
    { immediate: true },
);

const deletableWords = computed(() => props.words.filter((word) => word.can.delete));
const areAllDeletableWordsSelected = computed(
    () => deletableWords.value.length > 0 && selectedWordIds.value.length === deletableWords.value.length,
);

/** A word is changed when its field, without surrounding spaces, differs from its name. */
function isChanged(word: Word): boolean {
    return word.can.update && (draftNames[word.id] ?? word.name).trim() !== word.name;
}

const changedWords = computed<WordRename[]>(() =>
    props.words.filter(isChanged).map((word) => ({ id: word.id, name: (draftNames[word.id] ?? '').trim() })),
);

/** Puts back the name of the word and forgets its error. */
function restore(word: Word): void {
    draftNames[word.id] = word.name;
    const errors = { ...nameErrors.value };
    delete errors[word.id];
    nameErrors.value = errors;
}

const isConfirmingCancel = ref(false);

/** Leaving edit mode with unsaved changes asks for a confirmation. */
function cancel(): void {
    if (changedWords.value.length > 0) {
        isConfirmingCancel.value = true;
    } else {
        emit('cancel');
    }
}

function confirmCancel(): void {
    isConfirmingCancel.value = false;
    emit('cancel');
}

function toggleAllDeletableWords(): void {
    selectedWordIds.value = areAllDeletableWordsSelected.value ? [] : deletableWords.value.map((word) => word.id);
}

/** Maps the errors of the request (`words.N.name`, `words.N.id`) back to the words that were sent. */
function errorsByWordId(sentWords: WordRename[], errors: Record<string, string[]>): Record<string, string> {
    const result: Record<string, string> = {};
    for (const [field, messages] of Object.entries(errors)) {
        const match = /^words\.(\d+)\.(name|id)$/.exec(field);
        const word = match ? sentWords[Number(match[1])] : undefined;
        if (word && messages[0]) {
            result[word.id] ??= messages[0];
        }
    }

    return result;
}

async function save(): Promise<void> {
    const sentWords = changedWords.value;
    if (sentWords.length === 0) {
        emit('saved');

        return;
    }

    isSaving.value = true;
    nameErrors.value = {};
    saveError.value = null;
    try {
        await updateWords(props.wordGroupId, sentWords);
        emit('saved');
        showToast(sentWords.length > 1 ? `${sentWords.length} mots modifiés.` : '1 mot modifié.');
    } catch (error) {
        if (isAxiosError<ValidationErrorResponse>(error) && error.response?.status === 422) {
            nameErrors.value = errorsByWordId(sentWords, error.response.data.errors);
            saveError.value = 'Certains mots n’ont pas pu être enregistrés : corrigez-les puis réessayez.';
        } else {
            saveError.value = 'Impossible d’enregistrer les modifications. Rechargez la page et réessayez.';
        }
    } finally {
        isSaving.value = false;
    }
}

function askDeleteConfirmation(): void {
    deleteError.value = null;
    isConfirmingDelete.value = true;
}

async function confirmDelete(): Promise<void> {
    isDeleting.value = true;
    deleteError.value = null;
    const deletedCount = selectedWordIds.value.length;
    try {
        await deleteWords(props.wordGroupId, selectedWordIds.value);
        isConfirmingDelete.value = false;
        selectedWordIds.value = [];
        emit('deleted', deletedCount);
        showToast(deletedCount > 1 ? `${deletedCount} mots supprimés.` : '1 mot supprimé.');
    } catch {
        deleteError.value = 'Impossible de supprimer ces mots. Rechargez la page et réessayez.';
    } finally {
        isDeleting.value = false;
    }
}

const checkboxClass = 'size-4 shrink-0 rounded border-zinc-300 dark:border-zinc-700 accent-brand-600 dark:accent-brand-400';
</script>

<template>
    <div>
        <form novalidate @submit.prevent="save">
            <div
                class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 px-4 py-2 text-sm"
            >
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <label v-if="deletableWords.length > 0" class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :checked="areAllDeletableWordsSelected"
                            :indeterminate="selectedWordIds.length > 0 && !areAllDeletableWordsSelected"
                            :class="checkboxClass"
                            @change="toggleAllDeletableWords"
                        />
                        <span v-if="selectedWordIds.length === 0">Tout sélectionner</span>
                        <span v-else>
                            {{ selectedWordIds.length }} mot{{ selectedWordIds.length > 1 ? 's' : '' }}
                            sélectionné{{ selectedWordIds.length > 1 ? 's' : '' }}
                        </span>
                    </label>
                    <button
                        v-if="deletableWords.length > 0"
                        type="button"
                        :disabled="selectedWordIds.length === 0"
                        class="font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="askDeleteConfirmation"
                    >
                        Supprimer la sélection
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        :disabled="isSaving"
                        class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100"
                        @click="cancel"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="isSaving || changedWords.length === 0"
                        class="rounded-lg bg-brand-600 px-3 py-1.5 font-medium text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ isSaving ? 'Enregistrement…' : 'Enregistrer' }}<template v-if="!isSaving && changedWords.length > 0"> ({{ changedWords.length }})</template>
                    </button>
                </div>
            </div>
            <p v-if="saveError" role="alert" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ saveError }}</p>
    
            <ul
                class="mt-2 overflow-hidden divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs"
            >
                <li
                    v-for="word in words"
                    :key="word.id"
                    class="flex items-start gap-3 border-l-2 px-4 py-2"
                    :class="isChanged(word) ? 'border-l-brand-500 dark:border-l-brand-400 bg-brand-50/60 dark:bg-brand-950/40' : 'border-l-transparent'"
                >
                    <input
                        v-if="word.can.delete"
                        v-model="selectedWordIds"
                        type="checkbox"
                        :value="word.id"
                        :aria-label="`Sélectionner « ${word.name} »`"
                        class="mt-2"
                        :class="checkboxClass"
                    />
                    <span v-else class="size-4 shrink-0" />
    
                    <div class="min-w-0 flex-1">
                        <template v-if="word.can.update">
                            <label :for="`word-${word.id}`" class="sr-only">Mot « {{ word.name }} »</label>
                            <input
                                :id="`word-${word.id}`"
                                v-model="draftNames[word.id]"
                                type="text"
                                :maxlength="SKRIBBL_MAX_WORD_LENGTH"
                                autocomplete="off"
                                :aria-invalid="nameErrors[word.id] ? true : undefined"
                                :aria-describedby="nameErrors[word.id] ? `word-${word.id}-error` : undefined"
                                class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1 text-sm focus:border-brand-500 dark:focus:border-brand-400 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none aria-invalid:border-red-500"
                            />
                            <p v-if="nameErrors[word.id]" :id="`word-${word.id}-error`" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ nameErrors[word.id] }}
                            </p>
                            <p v-if="isChanged(word)" class="mt-1 flex flex-wrap items-baseline gap-x-2 text-xs">
                                <span class="font-medium text-brand-700 dark:text-brand-300">Modifié</span>
                                <span class="min-w-0 break-words text-zinc-500 dark:text-zinc-400">(avant : {{ word.name }})</span>
                                <button
                                    type="button"
                                    class="text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100"
                                    @click="restore(word)"
                                >
                                    Rétablir
                                </button>
                            </p>
                        </template>
                        <p v-else class="py-1 text-sm text-zinc-500 dark:text-zinc-400 break-words" title="Vous ne pouvez pas modifier ce mot">
                            {{ word.name }}
                        </p>
                    </div>
                </li>
            </ul>
        </form>

        <ConfirmDialog
            v-model:open="isConfirmingDelete"
            :title="selectedWordIds.length > 1 ? `Supprimer ${selectedWordIds.length} mots ?` : 'Supprimer ce mot ?'"
            :message="`${selectedWordIds.length > 1 ? 'Ces mots seront retirés' : 'Ce mot sera retiré'} du groupe. Cette action est irréversible.`"
            confirm-label="Supprimer"
            processing-label="Suppression…"
            :is-processing="isDeleting"
            :error="deleteError"
            @confirm="confirmDelete"
        />
        <ConfirmDialog
            v-model:open="isConfirmingCancel"
            :title="changedWords.length > 1 ? `Abandonner ${changedWords.length} modifications ?` : 'Abandonner la modification ?'"
            message="Les mots modifiés reprendront leur nom actuel."
            confirm-label="Abandonner"
            @confirm="confirmCancel"
        />
    </div>
</template>
