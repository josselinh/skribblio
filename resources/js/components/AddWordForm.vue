<script setup lang="ts">
import { nextTick, ref, useTemplateRef } from 'vue';
import { createWords } from '@/api/words';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import { parseWordList, SKRIBBL_SEPARATOR } from '@/lib/skribbl';
import type { CreateWordsResponse } from '@/types/api';
import type { Word } from '@/types/models';

const props = defineProps<{
    wordGroupId: string;
}>();

const emit = defineEmits<{
    added: [words: Word[]];
}>();

const textarea = useTemplateRef<HTMLTextAreaElement>('textarea');
const form = useForm({ names: '' });
const result = ref<CreateWordsResponse | null>(null);
const { showToast } = useToasts();

function plural(count: number, singular: string, pluralForm: string): string {
    return `${count} ${count > 1 ? pluralForm : singular}`;
}

/** Summary of the last submission: how many words were added and skipped. */
function summary(response: CreateWordsResponse): string {
    const parts = [`${plural(response.data.length, 'mot ajouté', 'mots ajoutés')}`];
    if (response.skipped.length > 0) {
        parts.push(`${plural(response.skipped.length, 'déjà présent ignoré', 'déjà présents ignorés')}`);
    }

    return parts.join(' · ');
}

/**
 * Adds the valid words and leaves the invalid ones in the field, so that the user only fixes
 * those. Keeps the focus in the field so that lists can be added one after another.
 */
async function submit(): Promise<void> {
    const names = parseWordList(form.data.names);
    if (names.length === 0) {
        return;
    }

    result.value = null;
    await form.submit(async () => {
        const response = await createWords(props.wordGroupId, names);
        result.value = response;
        form.data.names = response.invalid.map((invalidName) => invalidName.name).join(SKRIBBL_SEPARATOR);
        if (response.data.length > 0) {
            emit('added', response.data);
        }
        if (response.data.length > 0 || response.skipped.length > 0) {
            showToast(summary(response));
        }
    });

    await nextTick();
    textarea.value?.focus();
}
</script>

<template>
    <form novalidate @submit.prevent="submit">
        <label for="new-words" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Ajouter des mots</label>
        <p id="new-words-help" class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            Séparez les mots par des virgules ou des retours à la ligne. Entrée pour ajouter, Maj+Entrée pour aller à la ligne.
        </p>
        <div class="mt-2 flex items-start gap-2">
            <textarea
                id="new-words"
                ref="textarea"
                v-model="form.data.names"
                rows="3"
                placeholder="chat, chien, girafe…"
                autocomplete="off"
                :aria-invalid="form.errors.value.names || result?.invalid.length ? true : undefined"
                aria-describedby="new-words-help new-words-feedback"
                class="block w-full resize-y rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm shadow-xs focus:border-brand-500 dark:focus:border-brand-400 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none aria-invalid:border-red-500"
                @keydown.enter.exact.prevent="submit"
            ></textarea>
            <button
                type="submit"
                :disabled="form.isProcessing.value"
                class="shrink-0 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            >
                Ajouter
            </button>
        </div>
        <div id="new-words-feedback" aria-live="polite">
            <p v-if="form.errors.value.names" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ form.errors.value.names }}
            </p>
            <template v-else-if="result">
                <div v-if="result.invalid.length > 0" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    <p>
                        {{ plural(result.invalid.length, 'mot n’a pas pu être ajouté', 'mots n’ont pas pu être ajoutés') }}
                        (laissé{{ result.invalid.length > 1 ? 's' : '' }} dans le champ pour correction) :
                    </p>
                    <ul class="mt-0.5 list-disc pl-5">
                        <li v-for="invalidName in result.invalid" :key="invalidName.name">
                            <span class="font-medium">{{ invalidName.name }}</span> : {{ invalidName.message }}
                        </li>
                    </ul>
                </div>
            </template>
        </div>
    </form>
</template>
