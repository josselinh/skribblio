<script setup lang="ts">
import { visibilityLabel } from '@/lib/visibility';
import type { Visibility } from '@/types/models';

defineProps<{
    visibility: Visibility;
}>();
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium"
        :class="visibility === 'public' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'"
    >
        {{ visibilityLabel(visibility) }}
    </span>
</template>
