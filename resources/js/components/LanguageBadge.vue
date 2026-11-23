<script setup lang="ts">
import type { Locale } from '@/types/models';

/** Language of the words of a group or of the groups of a collection, as a short code. */
defineProps<{
    language: Locale;
}>();
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center rounded-full border border-zinc-300 dark:border-zinc-700 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-400"
        :title="$t(`languages.badgeHint.${language}`)"
    >
        <span aria-hidden="true">{{ $t(`languages.codes.${language}`) }}</span>
        <span class="sr-only">{{ $t(`languages.badgeHint.${language}`) }}</span>
    </span>
</template>
