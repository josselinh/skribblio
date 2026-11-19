<script setup lang="ts">
import { isAxiosError } from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { duplicateGroupCollection } from '@/api/groupCollections';
import { duplicateWordGroup } from '@/api/wordGroups';
import type { ValidationErrorResponse } from '@/types/api';
import type { FavoriteKind } from '@/types/models';

const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const router = useRouter();
const isDuplicating = ref(false);
const error = ref<string | null>(null);

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

/** Copies the item into an item of the current user and opens the copy with a status message. */
async function duplicate(): Promise<void> {
    isDuplicating.value = true;
    error.value = null;
    try {
        if (props.kind === 'word-groups') {
            const copy = await duplicateWordGroup(props.id);
            await router.push({
                name: 'word-groups.show',
                params: { id: copy.id },
                state: { status: 'Groupe dupliqué. Il est privé et fermé aux contributions.' },
            });
        } else {
            const copy = await duplicateGroupCollection(props.id);
            await router.push({
                name: 'group-collections.show',
                params: { id: copy.id },
                state: {
                    status: `Collection dupliquée. Elle et ses groupes copiés sont privés et fermés aux contributions.${hiddenGroupsNote(copy.hidden_word_groups_count)}`,
                },
            });
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
    <div>
        <button
            type="button"
            :disabled="isDuplicating"
            class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-xs hover:bg-zinc-50 disabled:opacity-60"
            @click="duplicate"
        >
            {{ isDuplicating ? 'Duplication…' : 'Dupliquer' }}
        </button>
        <p class="mt-1 text-xs text-zinc-500">{{ description }}</p>
        <p v-if="error" role="alert" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
