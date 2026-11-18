<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        isLoading?: boolean;
    }>(),
    { type: 'submit', isLoading: false },
);
</script>

<template>
    <button
        :type="type"
        :disabled="isLoading"
        class="inline-flex w-full items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
