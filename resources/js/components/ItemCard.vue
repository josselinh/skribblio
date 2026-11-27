<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router';
import CardMeta from '@/components/CardMeta.vue';
import CountBadge from '@/components/CountBadge.vue';
import ContributionsBadge from '@/components/ContributionsBadge.vue';
import LanguageBadge from '@/components/LanguageBadge.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import type { FavoriteKind, Locale, User, Visibility } from '@/types/models';

/**
 * Card of a group or a collection, in the order of an item's page title: the title in a tinted header (next to the
 * favorite star), the badges with the count (`CountBadge`) right after the language, then "Créé par X" and "Mise à
 * jour il y a 3 jours" (`CardMeta`), and an optional `footer` slot describing the item's place in its context (who
 * added a group to a collection), separated from what describes the item itself. In a card grid, these four parts
 * are the rows of a subgrid (`cardGridItemClass` from `@/lib/cards` on the `<li>`), so that they line up across the
 * cards of a grid row even when badges or footers take two lines. The footer keeps the right column free, under the
 * favorite star, for a selection checkbox laid over the card. The title's link is stretched over the whole card, so
 * that the card is clickable while other links (the creator's name, the footer's) and the favorite star stay
 * usable on top of it.
 */
defineProps<{
    kind: FavoriteKind;
    to: RouteLocationRaw;
    name: string;
    language: Locale;
    visibility: Visibility;
    acceptsContributions: boolean;
    /** Public and open to its members only. */
    contributionsMembersOnly: boolean;
    creator?: Pick<User, 'id' | 'name'>;
    /** Counted items, e.g. "42 mots". */
    countLabel?: string;
    updatedAt: string | null;
}>();
</script>

<template>
    <div class="relative row-span-4 grid grid-rows-subgrid">
        <article
            class="relative row-span-4 grid grid-rows-subgrid overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs transition hover:border-brand-300 dark:hover:border-brand-700 hover:shadow-sm has-[h3_a:focus-visible]:outline-2 has-[h3_a:focus-visible]:outline-brand-600 dark:has-[h3_a:focus-visible]:outline-brand-400"
        >
            <header class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800/70 py-3 pr-12 pl-4">
                <h3 class="truncate font-medium" :title="name">
                    <RouterLink :to="to" class="after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none">
                        {{ name }}
                    </RouterLink>
                </h3>
            </header>
            <div class="flex flex-wrap content-start items-center gap-1.5 px-4 pt-3">
                <LanguageBadge :language="language" />
                <CountBadge v-if="countLabel" :label="countLabel" />
                <VisibilityBadge :visibility="visibility" />
                <ContributionsBadge v-if="acceptsContributions" :members-only="contributionsMembersOnly" />
            </div>
            <div class="px-4 pt-2" :class="$slots.footer ? 'pb-3' : 'pb-4'">
                <CardMeta :kind="kind" :creator="creator" :updated-at="updatedAt" />
            </div>
            <div
                v-if="$slots.footer"
                class="border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 py-2.5 pr-12 pl-4 text-sm text-zinc-500 dark:text-zinc-400"
            >
                <slot name="footer" />
            </div>
        </article>
        <div class="absolute top-3 right-3 z-10">
            <slot name="favorite" />
        </div>
    </div>
</template>
