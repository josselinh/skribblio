<script setup lang="ts">
withDefaults(
    defineProps<{
        id: string;
        label: string;
        type?: string;
        autocomplete?: string;
        error?: string;
        required?: boolean;
    }>(),
    { type: 'text', autocomplete: undefined, error: undefined, required: false },
);

const model = defineModel<string>({ required: true });
</script>

<template>
    <div>
        <label :for="id" class="block text-sm font-medium">{{ label }}</label>
        <input
            :id="id"
            v-model="model"
            :type="type"
            :autocomplete="autocomplete"
            :required="required"
            :aria-invalid="error ? true : undefined"
            :aria-describedby="error ? `${id}-error` : undefined"
            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 px-3 py-2 text-sm shadow-xs focus:border-brand-500 dark:focus:border-brand-400 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none aria-invalid:border-red-500"
        />
        <p v-if="error" :id="`${id}-error`" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
</template>
