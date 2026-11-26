<script setup lang="ts">
/**
 * Count of a card's content ("42 mots", "8 groupes"), next to its badges: a figure rather than a status, so it is
 * squared off and neutral where the status badges are coloured pills.
 */
defineProps<{
    label: string;
}>();
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 tabular-nums"
    >
        {{ label }}
    </span>
</template>
