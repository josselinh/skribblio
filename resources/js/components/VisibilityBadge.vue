<script setup lang="ts">
import { visibilityLabels } from '@/lib/visibility';
import type { Visibility } from '@/types/models';

defineProps<{
    visibility: Visibility;
}>();
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium"
        :class="visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-600'"
    >
        {{ visibilityLabels[visibility] }}
    </span>
</template>
