<script setup lang="ts">
import { computed } from 'vue';
import type { FavoriteKind } from '@/types/models';

/** "Open to contributions" checkbox of the group and collection forms. */
const props = defineProps<{
    kind: FavoriteKind;
    error?: string;
}>();

const model = defineModel<boolean>({ required: true });

const description = computed(() =>
    props.kind === 'word-groups'
        ? 'Les personnes qui voient ce groupe peuvent y ajouter des mots, et modifier ou supprimer les leurs. Fermé, seul vous pouvez modifier la liste ; les mots déjà ajoutés sont conservés.'
        : 'Les personnes qui voient cette collection peuvent y ajouter des groupes, et retirer ceux qu’elles ont ajoutés. Fermée, seul vous pouvez modifier ses groupes ; ceux déjà ajoutés sont conservés.',
);
</script>

<template>
    <div>
        <label
            class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 bg-white p-3 has-checked:border-zinc-900 has-checked:bg-zinc-50"
        >
            <input v-model="model" type="checkbox" class="mt-1" />
            <span>
                <span class="block text-sm font-medium">Ouvert aux contributions</span>
                <span class="block text-sm text-zinc-500">{{ description }}</span>
            </span>
        </label>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
