<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { deleteWords, updateWords, type WordRename } from '@/api/words';
import AppIcon from '@/components/AppIcon.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import WordMeta from '@/components/WordMeta.vue';
import WordVoteButtons from '@/components/WordVoteButtons.vue';
import { useToasts } from '@/composables/useToasts';
import { SKRIBBL_MAX_WORD_LENGTH } from '@/lib/skribbl';
import type { ValidationErrorResponse } from '@/types/api';
import type { Word } from '@/types/models';

/**
 * A page of words on the edit page of a group: every word the user may update becomes a text field, saved all at
 * once (only the changed ones are sent; they are highlighted, each with a "Rétablir" button, and "Annuler" asks before
 * discarding them), and the words they may delete get a checkbox for a bulk deletion; the votes of each word end its
 * line, only displayed. The parent reloads the words on
 * `saved` and `deleted` (unsaved names are kept), and is told the number of unsaved changes through `changed`, so that
 * it can keep the user from changing page meanwhile.
 */
const props = defineProps<{
    wordGroupId: string;
    words: Word[];
}>();

const emit = defineEmits<{
    saved: [savedCount: number];
    deleted: [deletedCount: number];
    changed: [changedCount: number];
}>();

const { showToast } = useToasts();
const { t } = useI18n();

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

watch(() => changedWords.value.length, (changedCount) => emit('changed', changedCount), { immediate: true });

const isConfirmingCancel = ref(false);

/** Discarding the unsaved changes asks for a confirmation. */
function cancel(): void {
    isConfirmingCancel.value = true;
}

/** Puts back the name of every word and forgets their errors. */
function confirmCancel(): void {
    isConfirmingCancel.value = false;
    for (const word of props.words) {
        draftNames[word.id] = word.name;
    }
    nameErrors.value = {};
    saveError.value = null;
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
        emit('saved', 0);

        return;
    }

    isSaving.value = true;
    nameErrors.value = {};
    saveError.value = null;
    try {
        await updateWords(props.wordGroupId, sentWords);
        emit('saved', sentWords.length);
        showToast(t('words.editor.saved', sentWords.length));
    } catch (error) {
        if (isAxiosError<ValidationErrorResponse>(error) && error.response?.status === 422) {
            nameErrors.value = errorsByWordId(sentWords, error.response.data.errors);
            saveError.value = t('words.editor.invalid');
        } else {
            saveError.value = t('words.editor.saveFailed');
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
        showToast(t('words.editor.deleted', deletedCount));
    } catch {
        deleteError.value = t('words.editor.deleteFailed');
    } finally {
        isDeleting.value = false;
    }
}

const checkboxClass = 'size-4 pointer-coarse:size-5 shrink-0 rounded border-zinc-300 dark:border-zinc-700 accent-brand-600 dark:accent-brand-400';
</script>

<template>
    <div>
        <form novalidate @submit.prevent="save">
            <div
                v-if="deletableWords.length > 0"
                class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 px-4 py-2 text-sm"
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
                        <span v-if="selectedWordIds.length === 0">{{ $t('common.selectAll') }}</span>
                        <span v-else>{{ $t('words.editor.selected', selectedWordIds.length) }}</span>
                    </label>
                    <button
                        v-if="deletableWords.length > 0"
                        type="button"
                        :disabled="selectedWordIds.length === 0"
                        class="inline-flex items-center gap-1.5 font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="askDeleteConfirmation"
                    >
                        <AppIcon name="delete" />
                        {{ $t('common.deleteSelection') }}
                    </button>
                </div>
            </div>
    
            <ul
                :class="{ 'mt-2': deletableWords.length > 0 }"
                class="overflow-hidden divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs"
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
                        :aria-label="$t('words.editor.selectWord', { name: word.name })"
                        class="mt-2"
                        :class="checkboxClass"
                    />
                    <span v-else class="size-4 shrink-0" />
    
                    <div class="min-w-0 flex-1">
                        <template v-if="word.can.update">
                            <label :for="`word-${word.id}`" class="sr-only">{{ $t('words.editor.wordLabel', { name: word.name }) }}</label>
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
                                <span class="font-medium text-brand-700 dark:text-brand-300">{{ $t('words.editor.changed') }}</span>
                                <span class="min-w-0 break-words text-zinc-500 dark:text-zinc-400">{{ $t('words.editor.before', { name: word.name }) }}</span>
                                <button
                                    type="button"
                                    class="text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100"
                                    @click="restore(word)"
                                >
                                    {{ $t('words.editor.restore') }}
                                </button>
                            </p>
                        </template>
                        <p v-else class="py-1 text-sm text-zinc-500 dark:text-zinc-400 break-words" :title="$t('words.editor.notEditable')">
                            {{ word.name }}
                        </p>
                        <WordMeta class="mt-1" :word="word" />
                    </div>

                    <WordVoteButtons class="mt-1.5 shrink-0" :word="word" readonly />
                </li>
            </ul>

            <!-- Sticks to the bottom of the screen while there are changes, so that saving stays at hand under the list. -->
            <div
                v-if="changedWords.length > 0 || isSaving"
                class="sticky bottom-[max(0.5rem,env(safe-area-inset-bottom))] z-10 mt-2 flex flex-wrap items-center justify-end gap-x-4 gap-y-2 rounded-xl border border-brand-200 dark:border-brand-800 bg-white/95 dark:bg-zinc-900/95 px-4 py-2 text-sm shadow-lg backdrop-blur"
            >
                <!-- Phones only get the buttons, on one line: "Enregistrer (N)" already gives the number of changes. -->
                <p class="mr-auto hidden text-zinc-600 sm:block dark:text-zinc-400">{{ $t('words.editor.unsaved', changedWords.length) }}</p>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        :disabled="isSaving || changedWords.length === 0"
                        class="disabled:cursor-not-allowed disabled:opacity-50 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100"
                        @click="cancel"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="isSaving || changedWords.length === 0"
                        class="rounded-lg bg-brand-600 px-3 py-1.5 font-medium text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ isSaving ? $t('common.saving') : changedWords.length > 0 ? $t('words.editor.saveCount', { count: changedWords.length }) : $t('common.save') }}
                    </button>
                </div>
                <p v-if="saveError" role="alert" class="w-full text-red-600 dark:text-red-400">{{ saveError }}</p>
            </div>
        </form>

        <ConfirmDialog
            v-model:open="isConfirmingDelete"
            :title="$t('words.editor.deleteTitle', selectedWordIds.length)"
            :message="$t('words.editor.deleteMessage', selectedWordIds.length)"
            :confirm-label="$t('deletion.button')"
            :processing-label="$t('deletion.processing')"
            :is-processing="isDeleting"
            :error="deleteError"
            @confirm="confirmDelete"
        />
        <ConfirmDialog
            v-model:open="isConfirmingCancel"
            :title="$t('words.editor.discardTitle', changedWords.length)"
            :message="$t('words.editor.discardMessage')"
            :confirm-label="$t('words.editor.discard')"
            @confirm="confirmCancel"
        />
    </div>
</template>
