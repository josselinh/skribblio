<script setup lang="ts">
import { RouterLink } from 'vue-router';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { formatDateTime } from '@/lib/format';
import type { WordGroup } from '@/types/models';

defineProps<{
    wordGroup: WordGroup;
}>();
</script>

<template>
    <RouterLink
        :to="{ name: 'word-groups.show', params: { id: wordGroup.id } }"
        class="flex h-full flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-400 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-zinc-900"
    >
        <div class="flex items-start justify-between gap-2">
            <h2 class="truncate font-medium" :title="wordGroup.name">{{ wordGroup.name }}</h2>
            <VisibilityBadge :visibility="wordGroup.visibility" />
        </div>
        <p v-if="wordGroup.updated_at" class="text-sm text-zinc-500">
            Modifié le <time :datetime="wordGroup.updated_at">{{ formatDateTime(wordGroup.updated_at) }}</time>
        </p>
    </RouterLink>
</template>
