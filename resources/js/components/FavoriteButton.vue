<script setup lang="ts">
import { ref } from 'vue';
import { setFavorite } from '@/api/favorites';
import type { FavoriteKind } from '@/types/models';

/**
 * Star toggling a group or a collection in the user's favorites. The change is shown at once
 * and reverted if the request fails.
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

const isSaving = ref(false);
const hasFailed = ref(false);

async function toggle(): Promise<void> {
    if (isSaving.value) {
        return;
    }
    const newValue = !isFavorite.value;
    isFavorite.value = newValue;
    isSaving.value = true;
    hasFailed.value = false;
    try {
        await setFavorite(props.kind, props.id, newValue);
        emit('saved', newValue);
    } catch {
        isFavorite.value = !newValue;
        hasFailed.value = true;
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
        :title="hasFailed ? 'Impossible de modifier les favoris, veuillez réessayer.' : isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
        @click="toggle"
    >
        <span aria-hidden="true">{{ isFavorite ? '★' : '☆' }}</span>
    </button>
</template>
