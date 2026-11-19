<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router';
import ContributionsBadge from '@/components/ContributionsBadge.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { formatDateTime } from '@/lib/format';
import type { Visibility } from '@/types/models';

/**
 * Card of a group or a collection. The title's link is stretched over the whole card, so that the card
 * is clickable while other links (the creator's name in the `meta` slot) and the favorite star stay
 * usable on top of it. When the item is no longer accessible (a favorite that became private, a pending
 * request), the card is greyed out and not clickable.
 */
defineProps<{
    to: RouteLocationRaw;
    name: string;
    visibility: Visibility;
    acceptsContributions: boolean;
    /** Public and open to its members only. */
    contributionsMembersOnly: boolean;
    updatedAt: string | null;
    updatedLabel: string;
    isAccessible: boolean;
    inaccessibleMessage: string;
}>();
</script>

<template>
    <div class="relative h-full">
        <article
            class="relative flex h-full flex-col gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 pr-12 shadow-xs"
            :class="
                isAccessible
                    ? 'transition hover:border-brand-300 dark:hover:border-brand-700 hover:shadow-sm has-[h3_a:focus-visible]:outline-2 has-[h3_a:focus-visible]:outline-brand-600 dark:has-[h3_a:focus-visible]:outline-brand-400'
                    : 'opacity-50 grayscale'
            "
            :aria-disabled="isAccessible ? undefined : 'true'"
            :title="isAccessible ? undefined : inaccessibleMessage"
        >
            <div class="flex items-start justify-between gap-2">
                <h3 class="truncate font-medium" :title="name">
                    <RouterLink
                        v-if="isAccessible"
                        :to="to"
                        class="after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none"
                    >
                        {{ name }}
                    </RouterLink>
                    <span v-else class="cursor-not-allowed">{{ name }}</span>
                </h3>
                <VisibilityBadge :visibility="visibility" />
            </div>
            <slot name="meta" />
            <div v-if="acceptsContributions && isAccessible">
                <ContributionsBadge :members-only="contributionsMembersOnly" />
            </div>
            <p v-if="!isAccessible" class="text-sm text-zinc-600 dark:text-zinc-400">{{ inaccessibleMessage }}</p>
            <p v-else-if="updatedAt" class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ updatedLabel }} <time :datetime="updatedAt">{{ formatDateTime(updatedAt) }}</time>
            </p>
        </article>
        <div class="absolute top-3 right-3 z-10">
            <slot name="favorite" />
        </div>
    </div>
</template>
