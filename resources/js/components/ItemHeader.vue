<script setup lang="ts">
import { computed } from 'vue';
import ContributionsBadge from '@/components/ContributionsBadge.vue';
import LanguageBadge from '@/components/LanguageBadge.vue';
import RelativeTime from '@/components/RelativeTime.vue';
import ScrollingRow from '@/components/ScrollingRow.vue';
import UserLink from '@/components/UserLink.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind, Locale, User, Visibility } from '@/types/models';

/**
 * Read-only page title of a group or collection, built like the cards: a tinted header with the name (on two lines at
 * most) and, in its corner, the `after-title` slot (the favorite star, the actions menu); under it, the language and
 * visibility badges and whether contributions are open, then the creator and the last update date, and last, separated by a
 * line, the `actions` slot (buttons acting on the item: manage, duplicate, leave…, on one line scrolling horizontally when they do not fit), so that the export, under the card, opens
 * right under its own button. Pass the slot only when it has buttons, as it draws its own row.
 */
const props = defineProps<{
    name: string;
    language: Locale;
    visibility: Visibility;
    acceptsContributions: boolean;
    contributionsMembersOnly: boolean;
    kind: FavoriteKind;
    /** Shown as "Créé par X" under the badges (left out for visitors). */
    creator?: Pick<User, 'id' | 'name'>;
    /** Shown as "Dernière mise à jour le …" on the next line. */
    updatedAt: string | null;
}>();

const item = computed(() => itemKey(props.kind));
const isMembersOnly = computed(() => props.contributionsMembersOnly && props.visibility === 'public');
</script>

<template>
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <header class="flex items-start gap-2 rounded-t-xl border-b border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800/70 px-4 py-3">
            <h1 class="line-clamp-2 min-w-0 flex-1 text-2xl font-semibold break-words" :title="name">{{ name }}</h1>
            <div v-if="$slots['after-title']" class="flex shrink-0 items-center gap-1 pt-0.5">
                <slot name="after-title" />
            </div>
        </header>
        <div class="space-y-2 px-4 py-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <LanguageBadge :language="language" />
                <VisibilityBadge :visibility="visibility" />
                <ContributionsBadge v-if="acceptsContributions" :members-only="isMembersOnly" />
            </div>
            <div class="space-y-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                <p v-if="creator">
                    <i18n-t :keypath="`editing.createdBy.${item}`" scope="global">
                        <template #user><UserLink :user="creator" /></template>
                    </i18n-t>
                </p>
                <p v-if="updatedAt" class="text-zinc-500 dark:text-zinc-400">
                    <i18n-t keypath="editing.updatedOn" scope="global">
                        <template #date><RelativeTime :date="updatedAt" /></template>
                    </i18n-t>
                </p>
            </div>
        </div>
        <!-- One line scrolling horizontally when the buttons do not fit. Their dialogs, rendered beside them, keep wrapping their text. -->
        <div v-if="$slots.actions" class="border-t border-zinc-200 dark:border-zinc-800 px-4 py-3">
            <ScrollingRow class="gap-2 *:shrink-0 [&>:not(dialog)]:whitespace-nowrap">
                <slot name="actions" />
            </ScrollingRow>
        </div>
    </div>
</template>
