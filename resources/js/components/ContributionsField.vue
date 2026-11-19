<script setup lang="ts">
import { computed } from 'vue';
import type { FavoriteKind, Visibility } from '@/types/models';

/**
 * "Open to contributions" checkbox of the group and collection forms. On a public item, it also lets the
 * creator open the contributions to everyone or only to the members they accept through the share link.
 */
const props = defineProps<{
    kind: FavoriteKind;
    visibility: Visibility;
    error?: string;
    membersOnlyError?: string;
}>();

const model = defineModel<boolean>({ required: true });
const membersOnly = defineModel<boolean>('membersOnly', { required: true });

const isGroup = computed(() => props.kind === 'word-groups');

const description = computed(() =>
    isGroup.value
        ? 'Les personnes autorisées peuvent y ajouter des mots, et modifier ou supprimer les leurs. Fermé, seul vous pouvez modifier la liste ; les mots déjà ajoutés sont conservés.'
        : 'Les personnes autorisées peuvent y ajouter des groupes, et retirer ceux qu’elles ont ajoutés. Fermée, seul vous pouvez modifier ses groupes ; ceux déjà ajoutés sont conservés.',
);

const choices = computed(() => [
    {
        value: false,
        label: isGroup.value ? 'Tous ceux qui voient le groupe' : 'Tous ceux qui voient la collection',
        description: 'Chaque utilisateur connecté peut contribuer.',
    },
    {
        value: true,
        label: 'Uniquement les personnes que j’autorise',
        description:
            'Tout le monde peut consulter, mais seules les personnes que vous acceptez via le lien de partage peuvent contribuer.',
    },
]);
</script>

<template>
    <div>
        <label
            class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
        >
            <input v-model="model" type="checkbox" class="mt-1" />
            <span>
                <span class="block text-sm font-medium">Ouvert aux contributions</span>
                <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ description }}</span>
            </span>
        </label>
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

        <fieldset v-if="model && visibility === 'public'" class="mt-2 space-y-2 pl-6">
            <legend class="mb-2 text-sm font-medium">Qui peut contribuer ?</legend>
            <label
                v-for="choice in choices"
                :key="String(choice.value)"
                class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
            >
                <input v-model="membersOnly" type="radio" :value="choice.value" class="mt-1" />
                <span>
                    <span class="block text-sm font-medium">{{ choice.label }}</span>
                    <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ choice.description }}</span>
                </span>
            </label>
            <p v-if="membersOnlyError" class="text-sm text-red-600 dark:text-red-400">{{ membersOnlyError }}</p>
        </fieldset>
    </div>
</template>
