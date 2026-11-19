<script setup lang="ts">
import { isAxiosError } from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { duplicateGroupCollection } from '@/api/groupCollections';
import { duplicateWordGroup } from '@/api/wordGroups';
import ModalDialog from '@/components/ModalDialog.vue';
import { useToasts } from '@/composables/useToasts';
import type { ValidationErrorResponse } from '@/types/api';
import type { FavoriteKind } from '@/types/models';

/**
 * "Dupliquer" button of a group or collection page, confirmed in a modal explaining what is copied; opens the copy
 * with a status message.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const router = useRouter();
const { showToast } = useToasts();
const isConfirming = ref(false);
const isDuplicating = ref(false);
const error = ref<string | null>(null);

const title = props.kind === 'word-groups' ? 'Dupliquer ce groupe ?' : 'Dupliquer cette collection ?';
const description =
    props.kind === 'word-groups'
        ? 'Crée une copie privée de ce groupe et de ses mots, dont vous êtes l’auteur.'
        : 'Crée une copie privée de cette collection et des groupes que vous voyez, avec leurs mots, dont vous êtes l’auteur. Vos propres groupes sont repris tels quels.';

function hiddenGroupsNote(count: number): string {
    if (count === 0) {
        return '';
    }

    return count === 1 ? ' 1 groupe masqué n’a pas été copié.' : ` ${count} groupes masqués n’ont pas été copiés.`;
}

function askConfirmation(): void {
    error.value = null;
    isConfirming.value = true;
}

/** Copies the item into an item of the current user, opens the copy and confirms it in a notification. */
async function duplicate(): Promise<void> {
    isDuplicating.value = true;
    error.value = null;
    try {
        if (props.kind === 'word-groups') {
            const copy = await duplicateWordGroup(props.id);
            isConfirming.value = false;
            await router.push({ name: 'word-groups.show', params: { id: copy.id } });
            showToast('Groupe dupliqué. Il est privé et fermé aux contributions.');
        } else {
            const copy = await duplicateGroupCollection(props.id);
            isConfirming.value = false;
            await router.push({ name: 'group-collections.show', params: { id: copy.id } });
            showToast(
                `Collection dupliquée. Elle et ses groupes copiés sont privés et fermés aux contributions.${hiddenGroupsNote(copy.hidden_word_groups_count)}`,
            );
        }
    } catch (caughtError) {
        error.value =
            isAxiosError<ValidationErrorResponse>(caughtError) && caughtError.response?.status === 422
                ? caughtError.response.data.message
                : 'Impossible de dupliquer. Réessayez.';
    } finally {
        isDuplicating.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800"
        aria-haspopup="dialog"
        @click="askConfirmation"
    >
        Dupliquer
    </button>

    <ModalDialog v-model:open="isConfirming" :title="title" :is-dismissible="!isDuplicating">
        <p>{{ description }}</p>
        <p v-if="error" role="alert" class="mt-3 text-red-600 dark:text-red-400">{{ error }}</p>

        <template #actions>
            <button
                type="button"
                :disabled="isDuplicating"
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="isConfirming = false"
            >
                Annuler
            </button>
            <button
                type="button"
                autofocus
                :disabled="isDuplicating"
                class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="duplicate"
            >
                {{ isDuplicating ? 'Duplication…' : 'Dupliquer' }}
            </button>
        </template>
    </ModalDialog>
</template>
