<script setup lang="ts">
import { nextTick, ref, useTemplateRef } from 'vue';
import { deleteWord, updateWord } from '@/api/words';
import { useForm } from '@/composables/useForm';
import { formatDateTime } from '@/lib/format';
import type { Word } from '@/types/models';

const props = defineProps<{
    word: Word;
}>();

const emit = defineEmits<{
    updated: [word: Word];
    deleted: [word: Word];
}>();

type Mode = 'view' | 'edit' | 'confirm-delete';
const mode = ref<Mode>('view');
const isDeleting = ref(false);
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
        emit('updated', await updateWord(props.word.id, data));
    });

    if (isSuccessful) {
        mode.value = 'view';
    }
}

async function confirmDelete(): Promise<void> {
    isDeleting.value = true;
    try {
        await deleteWord(props.word.id);
        emit('deleted', props.word);
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
                    autocomplete="off"
                    :aria-invalid="form.errors.value.name ? true : undefined"
                    class="block w-full min-w-32 rounded-md border border-zinc-300 px-2 py-1 text-sm focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-none aria-invalid:border-red-500"
                />
                <p v-if="form.errors.value.name" class="mt-1 text-xs font-normal text-red-600">{{ form.errors.value.name }}</p>
            </form>
            <template v-else>{{ word.name }}</template>
        </td>
        <td class="px-4 py-2.5 text-zinc-600">{{ word.creator.name }}</td>
        <td class="px-4 py-2.5 whitespace-nowrap text-zinc-600">
            <time v-if="word.created_at" :datetime="word.created_at">{{ formatDateTime(word.created_at) }}</time>
        </td>
        <td class="px-4 py-2.5 whitespace-nowrap text-zinc-600">
            <time v-if="word.updated_at" :datetime="word.updated_at">{{ formatDateTime(word.updated_at) }}</time>
        </td>
        <td class="px-4 py-2.5 text-right whitespace-nowrap">
            <div v-if="mode === 'edit'" class="flex justify-end gap-3">
                <button type="button" class="text-zinc-500 hover:text-zinc-900" @click="mode = 'view'">Annuler</button>
                <button type="button" :disabled="form.isProcessing.value" class="font-medium hover:underline" @click="save">
                    Enregistrer
                </button>
            </div>
            <div v-else-if="mode === 'confirm-delete'" class="flex justify-end gap-3">
                <span class="text-zinc-500">Supprimer ?</span>
                <button type="button" class="text-zinc-500 hover:text-zinc-900" @click="mode = 'view'">Non</button>
                <button
                    type="button"
                    :disabled="isDeleting"
                    class="font-medium text-red-600 hover:underline"
                    @click="confirmDelete"
                >
                    Oui
                </button>
            </div>
            <div v-else class="flex justify-end gap-3">
                <button v-if="word.can.update" type="button" class="text-zinc-600 hover:text-zinc-900" @click="startEditing">
                    Modifier
                </button>
                <button
                    v-if="word.can.delete"
                    type="button"
                    class="text-red-600 hover:text-red-800"
                    @click="mode = 'confirm-delete'"
                >
                    Supprimer
                </button>
            </div>
        </td>
    </tr>
</template>
