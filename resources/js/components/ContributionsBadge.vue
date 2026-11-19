<script setup lang="ts">
/** Shows that an item is open to contributions, to everyone who sees it or only to its members. */
defineProps<{
    membersOnly?: boolean;
}>();
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium"
        :class="membersOnly ? 'bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300' : 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300'"
        :title="
            membersOnly
                ? 'Seules les personnes autorisées par le créateur peuvent contribuer.'
                : 'Les personnes qui y ont accès peuvent contribuer.'
        "
    >
        {{ membersOnly ? 'Contributions sur autorisation' : 'Contributions ouvertes' }}
    </span>
</template>
