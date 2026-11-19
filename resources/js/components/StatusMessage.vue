<script setup lang="ts">
defineProps<{
    message: string | null;
}>();
</script>

<template>
    <p v-if="message" role="status" class="mb-4 rounded-lg bg-green-50 dark:bg-green-950/40 px-3 py-2 text-sm text-green-700 dark:text-green-400">
        {{ message }}
    </p>
</template>
