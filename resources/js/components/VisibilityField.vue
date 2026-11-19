<script setup lang="ts">
import { visibilityDescriptions, visibilityLabels } from '@/lib/visibility';
import type { Visibility } from '@/types/models';

defineProps<{
    error?: string;
}>();

const model = defineModel<Visibility>({ required: true });

const visibilities: Visibility[] = ['private', 'public'];
</script>

<template>
    <fieldset>
        <legend class="text-sm font-medium">Visibilité</legend>
        <div class="mt-2 grid gap-2">
            <label
                v-for="visibility in visibilities"
                :key="visibility"
                class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
            >
                <input v-model="model" type="radio" :value="visibility" class="mt-1" />
                <span>
                    <span class="block text-sm font-medium">{{ visibilityLabels[visibility] }}</span>
                    <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ visibilityDescriptions[visibility] }}</span>
                </span>
            </label>
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </fieldset>
</template>
