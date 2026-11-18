<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { formatDateTime } from '@/lib/format';
import type { Visibility } from '@/types/models';

/**
 * Card of a group or a collection. The whole card links to the item, except when it is no longer
 * accessible (a favorite that became private): it is then greyed out and not clickable. The `favorite`
 * slot (star) sits outside the link so that it stays usable in both cases.
 */
defineProps<{
    to: RouteLocationRaw;
    name: string;
    visibility: Visibility;
    updatedAt: string | null;
    updatedLabel: string;
    isAccessible: boolean;
    inaccessibleMessage: string;
}>();
</script>

<template>
    <div class="relative h-full">
        <component
            :is="isAccessible ? RouterLink : 'div'"
            v-bind="isAccessible ? { to } : { 'aria-disabled': 'true', title: inaccessibleMessage }"
            class="flex h-full flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 pr-12 shadow-xs"
            :class="
                isAccessible
                    ? 'transition hover:border-zinc-400 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-zinc-900'
                    : 'cursor-not-allowed opacity-50 grayscale'
            "
        >
            <div class="flex items-start justify-between gap-2">
                <h3 class="truncate font-medium" :title="name">{{ name }}</h3>
                <VisibilityBadge :visibility="visibility" />
            </div>
            <slot name="meta" />
            <p v-if="!isAccessible" class="text-sm text-zinc-600">{{ inaccessibleMessage }}</p>
            <p v-else-if="updatedAt" class="text-sm text-zinc-500">
                {{ updatedLabel }} <time :datetime="updatedAt">{{ formatDateTime(updatedAt) }}</time>
            </p>
        </component>
        <div class="absolute top-3 right-3">
            <slot name="favorite" />
        </div>
    </div>
</template>
