<script setup lang="ts">
import { nextTick, useTemplateRef } from 'vue';
import { createWord } from '@/api/words';
import { useForm } from '@/composables/useForm';
import { SKRIBBL_MAX_WORD_LENGTH } from '@/lib/skribbl';
import type { Word } from '@/types/models';

const props = defineProps<{
    wordGroupId: string;
}>();

const emit = defineEmits<{
    added: [word: Word];
}>();

const input = useTemplateRef<HTMLInputElement>('input');
const form = useForm({ name: '' });

/** Keeps the focus in the field so that words can be added one after another with Enter. */
async function submit(): Promise<void> {
    const isSuccessful = await form.submit(async (data) => {
        emit('added', await createWord(props.wordGroupId, data));
    });

    if (isSuccessful) {
        form.reset();
    }
    await nextTick();
    input.value?.focus();
}
</script>

<template>
    <form novalidate @submit.prevent="submit">
        <label for="new-word" class="sr-only">Nouveau mot</label>
        <div class="flex gap-2">
            <input
                id="new-word"
                ref="input"
                v-model="form.data.name"
                type="text"
                :maxlength="SKRIBBL_MAX_WORD_LENGTH"
                placeholder="Nouveau mot…"
                autocomplete="off"
                :aria-invalid="form.errors.value.name ? true : undefined"
                aria-describedby="new-word-error"
                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 focus:outline-none aria-invalid:border-red-500"
            />
            <button
                type="submit"
                :disabled="form.isProcessing.value"
                class="shrink-0 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-60"
            >
                Ajouter
            </button>
        </div>
        <p v-if="form.errors.value.name" id="new-word-error" class="mt-1 text-sm text-red-600">
            {{ form.errors.value.name }}
        </p>
    </form>
</template>
