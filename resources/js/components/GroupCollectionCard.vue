<script setup lang="ts">
import { RouterLink } from 'vue-router';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { formatDateTime } from '@/lib/format';
import type { GroupCollection } from '@/types/models';

defineProps<{
    groupCollection: GroupCollection;
}>();
</script>

<template>
    <RouterLink
        :to="{ name: 'group-collections.show', params: { id: groupCollection.id } }"
        class="flex h-full flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-400 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-zinc-900"
    >
        <div class="flex items-start justify-between gap-2">
            <h3 class="truncate font-medium" :title="groupCollection.name">{{ groupCollection.name }}</h3>
            <VisibilityBadge :visibility="groupCollection.visibility" />
        </div>
        <p v-if="groupCollection.updated_at" class="text-sm text-zinc-500">
            Modifiée le <time :datetime="groupCollection.updated_at">{{ formatDateTime(groupCollection.updated_at) }}</time>
        </p>
    </RouterLink>
</template>
