<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind, ShareType } from '@/types/models';

/**
 * Choice of the type of sharing granted to a member, when accepting their request: partial (their own
 * contributions) or full (everyone's).
 */
const props = defineProps<{
    kind: FavoriteKind;
}>();

const model = defineModel<ShareType>({ required: true });

const { t } = useI18n();

const shareTypes: ShareType[] = ['partial', 'full'];

const choices = computed(() =>
    shareTypes.map((shareType) => ({
        value: shareType,
        label: t(`sharing.shareType.${shareType}.label`),
        description: t(`sharing.shareType.${shareType}.description.${itemKey(props.kind)}`),
    })),
);
</script>

<template>
    <fieldset class="mt-4 space-y-2">
        <legend class="mb-2 text-sm font-medium">{{ $t('sharing.shareType.label') }}</legend>
        <label
            v-for="choice in choices"
            :key="choice.value"
            class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
        >
            <input v-model="model" type="radio" :value="choice.value" class="mt-1" />
            <span>
                <span class="block text-sm font-medium">{{ choice.label }}</span>
                <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ choice.description }}</span>
            </span>
        </label>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t(`sharing.shareType.note.${itemKey(kind)}`) }}</p>
    </fieldset>
</template>
