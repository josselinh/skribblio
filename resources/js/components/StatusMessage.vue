<script setup lang="ts">
defineProps<{
    message: string | null;
}>();
</script>

<template>
    <p v-if="message" role="status" class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
        {{ message }}
    </p>
</template>
