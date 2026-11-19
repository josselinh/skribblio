<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { deleteGroupCollection } from '@/api/groupCollections';
import { deleteWordGroup } from '@/api/wordGroups';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import type { FavoriteKind } from '@/types/models';

/**
 * "Supprimer" button of a group or collection page, confirmed in a modal (`ConfirmDialog`); once deleted, goes back to the
 * dashboard with a status message.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const router = useRouter();
const isConfirming = ref(false);
const isDeleting = ref(false);
const error = ref<string | null>(null);

const title = props.kind === 'word-groups' ? 'Supprimer ce groupe ?' : 'Supprimer cette collection ?';
const warning =
    props.kind === 'word-groups'
        ? 'Le groupe et tous ses mots seront supprimés, et il sera retiré des collections qui le contiennent. Cette action est irréversible.'
        : 'La collection sera supprimée, mais pas les groupes qu’elle contient. Cette action est irréversible.';

function askConfirmation(): void {
    error.value = null;
    isConfirming.value = true;
}

async function confirmDelete(): Promise<void> {
    isDeleting.value = true;
    error.value = null;
    try {
        if (props.kind === 'word-groups') {
            await deleteWordGroup(props.id);
        } else {
            await deleteGroupCollection(props.id);
        }
        isConfirming.value = false;
        await router.push({
            name: 'dashboard',
            state: { status: props.kind === 'word-groups' ? 'Groupe supprimé.' : 'Collection supprimée.' },
        });
    } catch {
        error.value = 'Impossible de supprimer. Réessayez.';
    } finally {
        isDeleting.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        class="rounded-lg border border-red-200 dark:border-red-900 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40"
        @click="askConfirmation"
    >
        Supprimer
    </button>

    <ConfirmDialog
        v-model:open="isConfirming"
        :title="title"
        :message="warning"
        confirm-label="Supprimer définitivement"
        processing-label="Suppression…"
        :is-processing="isDeleting"
        :error="error"
        @confirm="confirmDelete"
    />
</template>
