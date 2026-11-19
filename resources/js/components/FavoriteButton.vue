<script setup lang="ts">
import { ref } from 'vue';
import { setFavorite } from '@/api/favorites';
import { useToasts } from '@/composables/useToasts';
import type { FavoriteKind } from '@/types/models';

/**
 * Star toggling a group or a collection in the user's favorites. The change is shown at once, confirmed in a
 * notification once saved, and reverted (with an error notification) if the request fails.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    name: string;
}>();

const isFavorite = defineModel<boolean>({ required: true });

const emit = defineEmits<{
    /** Emitted once the server confirmed the change. */
    saved: [isFavorite: boolean];
}>();

const { showToast } = useToasts();

const isSaving = ref(false);

function savedMessage(isNowFavorite: boolean): string {
    const item = props.kind === 'word-groups' ? `Groupe « ${props.name} »` : `Collection « ${props.name} »`;
    const agreement = props.kind === 'word-groups' ? '' : 'e';

    return isNowFavorite ? `${item} ajouté${agreement} aux favoris.` : `${item} retiré${agreement} des favoris.`;
}

async function toggle(): Promise<void> {
    if (isSaving.value) {
        return;
    }
    const newValue = !isFavorite.value;
    isFavorite.value = newValue;
    isSaving.value = true;
    try {
        await setFavorite(props.kind, props.id, newValue);
        emit('saved', newValue);
        showToast(savedMessage(newValue));
    } catch {
        isFavorite.value = !newValue;
        showToast('Impossible de modifier les favoris, veuillez réessayer.', 'error');
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        class="rounded-md p-1 text-lg leading-none transition hover:bg-zinc-100 dark:hover:bg-zinc-800 focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400"
        :class="isFavorite ? 'text-amber-500' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'"
        :aria-pressed="isFavorite"
        :aria-label="isFavorite ? `Retirer ${name} des favoris` : `Ajouter ${name} aux favoris`"
        :title="isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
        @click="toggle"
    >
        <span aria-hidden="true">{{ isFavorite ? '★' : '☆' }}</span>
    </button>
</template>
