<script setup lang="ts">
import { nextTick, ref, useTemplateRef } from 'vue';
import { deleteWord, updateWord } from '@/api/words';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import UserLink from '@/components/UserLink.vue';
import WordVoteButtons from '@/components/WordVoteButtons.vue';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import { SKRIBBL_MAX_WORD_LENGTH } from '@/lib/skribbl';
import { formatDateTime } from '@/lib/format';
import type { Word } from '@/types/models';

const props = defineProps<{
    word: Word;
    /** Shows the votes column. */
    showVotes: boolean;
}>();

const { showToast } = useToasts();

const emit = defineEmits<{
    updated: [word: Word];
    deleted: [word: Word];
}>();

type Mode = 'view' | 'edit';
const mode = ref<Mode>('view');
const isConfirmingDelete = ref(false);
const isDeleting = ref(false);
const deleteError = ref<string | null>(null);
const input = useTemplateRef<HTMLInputElement>('input');
const form = useForm({ name: props.word.name });

async function startEditing(): Promise<void> {
    form.data.name = props.word.name;
    form.errors.value = {};
    mode.value = 'edit';
    await nextTick();
    input.value?.select();
}

async function save(): Promise<void> {
    const isSuccessful = await form.submit(async (data) => {
        const word = await updateWord(props.word.id, data);
        emit('updated', word);
        showToast(`Mot renommé en « ${word.name} ».`);
    });

    if (isSuccessful) {
        mode.value = 'view';
    }
}

function askDeleteConfirmation(): void {
    deleteError.value = null;
    isConfirmingDelete.value = true;
}

async function confirmDelete(): Promise<void> {
    isDeleting.value = true;
    deleteError.value = null;
    try {
        await deleteWord(props.word.id);
        isConfirmingDelete.value = false;
        emit('deleted', props.word);
        showToast(`Mot « ${props.word.name} » supprimé.`);
    } catch {
        deleteError.value = 'Impossible de supprimer ce mot. Réessayez.';
    } finally {
        isDeleting.value = false;
    }
}
</script>

<template>
    <tr class="align-top">
        <td class="px-4 py-2.5 font-medium">
            <form v-if="mode === 'edit'" novalidate @submit.prevent="save" @keydown.esc="mode = 'view'">
                <label :for="`word-${word.id}`" class="sr-only">Mot</label>
                <input
                    :id="`word-${word.id}`"
                    ref="input"
                    v-model="form.data.name"
                    type="text"
                    :maxlength="SKRIBBL_MAX_WORD_LENGTH"
                    autocomplete="off"
                    :aria-invalid="form.errors.value.name ? true : undefined"
                    class="block w-full min-w-32 rounded-md border border-zinc-300 dark:border-zinc-700 px-2 py-1 text-sm focus:border-brand-500 dark:focus:border-brand-400 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none aria-invalid:border-red-500"
                />
                <p v-if="form.errors.value.name" class="mt-1 text-xs font-normal text-red-600 dark:text-red-400">{{ form.errors.value.name }}</p>
            </form>
            <template v-else>{{ word.name }}</template>
        </td>
        <td v-if="showVotes" class="px-4 py-2">
            <WordVoteButtons :word="word" @updated="emit('updated', $event)" />
        </td>
        <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
            <UserLink v-if="word.creator" :user="word.creator" />
            <span v-else class="text-zinc-400 dark:text-zinc-500 italic">Compte supprimé</span>
        </td>
        <td class="px-4 py-2.5 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
            <time v-if="word.created_at" :datetime="word.created_at">{{ formatDateTime(word.created_at) }}</time>
        </td>
        <td class="px-4 py-2.5 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
            <time v-if="word.updated_at" :datetime="word.updated_at">{{ formatDateTime(word.updated_at) }}</time>
        </td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            <div v-if="mode === 'edit'" class="flex justify-end gap-3">
                <button type="button" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100" @click="mode = 'view'">Annuler</button>
                <button type="button" :disabled="form.isProcessing.value" class="font-medium text-brand-700 dark:text-brand-300 hover:underline" @click="save">
                    Enregistrer
                </button>
            </div>
            <div v-else class="flex justify-end gap-3">
                <button v-if="word.can.update" type="button" class="text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100" @click="startEditing">
                    Modifier
                </button>
                <button
                    v-if="word.can.delete"
                    type="button"
                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                    @click="askDeleteConfirmation"
                >
                    Supprimer
                </button>
            </div>
            <ConfirmDialog
                v-model:open="isConfirmingDelete"
                :title="`Supprimer « ${word.name} » ?`"
                message="Ce mot sera retiré du groupe. Cette action est irréversible."
                confirm-label="Supprimer"
                processing-label="Suppression…"
                :is-processing="isDeleting"
                :error="deleteError"
                @confirm="confirmDelete"
            />
        </td>
    </tr>
</template>
