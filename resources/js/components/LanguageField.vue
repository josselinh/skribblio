<script setup lang="ts">
import { LOCALES } from '@/lib/i18n';
import type { FavoriteKind, Locale } from '@/types/models';

/**
 * Language of the content of a group (its words) or of a collection (its groups), in the creation forms only:
 * it cannot be changed afterwards.
 */
defineProps<{
    kind: FavoriteKind;
    error?: string;
}>();

const model = defineModel<Locale>({ required: true });
</script>

<template>
    <fieldset>
        <legend class="text-sm font-medium">
            {{ kind === 'word-groups' ? $t('languages.field.wordGroup') : $t('languages.field.groupCollection') }}
        </legend>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ kind === 'word-groups' ? $t('languages.field.wordGroupHint') : $t('languages.field.groupCollectionHint') }}
        </p>
        <div class="mt-2 grid grid-cols-2 gap-2">
            <label
                v-for="language in LOCALES"
                :key="language"
                class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
            >
                <input v-model="model" type="radio" :value="language" />
                <span class="text-sm font-medium">{{ $t(`languages.names.${language}`) }}</span>
            </label>
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </fieldset>
</template>
