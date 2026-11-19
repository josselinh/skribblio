<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { leaveItem } from '@/api/sharing';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import type { FavoriteKind, Visibility } from '@/types/models';

/**
 * "Quitter" button of a group or collection page, for its members, confirmed in a modal (`ConfirmDialog`). Leaving a
 * private item goes back to the dashboard with a status message; leaving a public one (whose contributions are
 * restricted to its members) keeps the page, which reloads it on `left`.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    visibility: Visibility;
}>();

const emit = defineEmits<{
    left: [status: string];
}>();

const router = useRouter();
const isConfirming = ref(false);
const isLeaving = ref(false);
const error = ref<string | null>(null);

const isGroup = computed(() => props.kind === 'word-groups');
const title = computed(() => (isGroup.value ? 'Quitter ce groupe ?' : 'Quitter cette collection ?'));
const message = computed(() => {
    const item = isGroup.value ? 'ce groupe' : 'cette collection';
    const consequence =
        props.visibility === 'private'
            ? `Vous ne pourrez plus voir ${item}${isGroup.value ? '' : ' ni les groupes privés qu’elle vous montrait'}, qui seront retirés de vos favoris.`
            : `Vous pourrez toujours voir ${item}, mais plus y contribuer.`;

    return `${consequence} Vos contributions sont conservées. Pour revenir, il faudra redemander l’accès au créateur.`;
});

function askConfirmation(): void {
    error.value = null;
    isConfirming.value = true;
}

async function confirmLeave(): Promise<void> {
    isLeaving.value = true;
    error.value = null;
    try {
        await leaveItem(props.kind, props.id);
        isConfirming.value = false;
        const status = isGroup.value ? 'Vous avez quitté le groupe.' : 'Vous avez quitté la collection.';
        if (props.visibility === 'private') {
            await router.push({ name: 'dashboard', state: { status } });
        } else {
            emit('left', status);
        }
    } catch {
        error.value = 'Impossible de quitter. Réessayez.';
    } finally {
        isLeaving.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        class="rounded-lg border border-red-200 dark:border-red-900 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40"
        @click="askConfirmation"
    >
        Quitter
    </button>

    <ConfirmDialog
        v-model:open="isConfirming"
        :title="title"
        :message="message"
        confirm-label="Quitter"
        processing-label="Départ…"
        :is-processing="isLeaving"
        :error="error"
        @confirm="confirmLeave"
    />
</template>
