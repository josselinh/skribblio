<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
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

const { t } = useI18n();

const isGroup = computed(() => props.kind === 'word-groups');

const description = computed(() =>
    isGroup.value
        ? t('contributions.field.description.wordGroup')
        : t('contributions.field.description.groupCollection'),
);

const choices = computed(() => [
    {
        value: false,
        label: isGroup.value ? t('contributions.field.everyone.wordGroup') : t('contributions.field.everyone.groupCollection'),
        description: t('contributions.field.everyone.description'),
    },
    {
        value: true,
        label: t('contributions.field.membersOnly.label'),
        description: t('contributions.field.membersOnly.description'),
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
                <span class="block text-sm font-medium">{{ $t('contributions.field.label') }}</span>
                <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ description }}</span>
            </span>
        </label>
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

        <fieldset v-if="model && visibility === 'public'" class="mt-2 space-y-2 pl-6">
            <legend class="mb-2 text-sm font-medium">{{ $t('contributions.field.who') }}</legend>
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
