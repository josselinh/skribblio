<script setup lang="ts" generic="T extends { id: string }">
import { RouterLink, type RouteLocationRaw } from 'vue-router';
import PaginationNav from '@/components/PaginationNav.vue';
import type { PaginatedResponse } from '@/types/api';

/**
 * Section listing groups or collections as cards: a title with the total, optional header actions,
 * the cards rendered through the `item` slot, then a "see all" link (preview) and/or the pagination.
 * Used by the dashboard and the search page.
 */
withDefaults(
    defineProps<{
        sectionId: string;
        title: string;
        result: PaginatedResponse<T> | null;
        isLoading: boolean;
        hasFailed: boolean;
        /** Shown when the list is empty, unless the `empty` slot is given. */
        emptyMessage?: string;
        /** Shown in preview mode when there are more results than displayed. */
        seeAll?: { label: string; to: RouteLocationRaw };
        paginated: boolean;
        /** Query string parameter holding the page (several paginated lists on one page). */
        pageParam?: string;
    }>(),
    { emptyMessage: '', seeAll: undefined, pageParam: 'page' },
);

defineEmits<{
    retry: [];
}>();

defineSlots<{
    item(props: { item: T }): unknown;
    actions?(): unknown;
    empty?(): unknown;
}>();
</script>

<template>
    <section :aria-labelledby="`${sectionId}-title`">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 :id="`${sectionId}-title`" class="text-lg font-semibold">
                {{ title }}
                <span v-if="result" class="font-normal text-zinc-500">({{ result.meta.total }})</span>
            </h2>
            <slot name="actions" />
        </div>

        <p v-if="isLoading && !result" class="mt-4 text-zinc-500">Chargement…</p>
        <p v-else-if="hasFailed" class="mt-4 text-red-600">
            Impossible de charger la liste.
            <button type="button" class="underline" @click="$emit('retry')">Réessayer</button>
        </p>
        <div v-else-if="result?.data.length === 0" class="mt-4">
            <slot name="empty">
                <p class="text-zinc-500">{{ emptyMessage }}</p>
            </slot>
        </div>
        <template v-else-if="result">
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" :class="{ 'opacity-60': isLoading }">
                <li v-for="item in result.data" :key="item.id">
                    <slot name="item" :item="item" />
                </li>
            </ul>
            <RouterLink v-if="seeAll" :to="seeAll.to" class="mt-4 inline-block text-sm font-medium underline">
                {{ seeAll.label }}
            </RouterLink>
            <PaginationNav
                v-if="paginated"
                class="mt-6"
                :page-param="pageParam"
                :current-page="result.meta.current_page"
                :last-page="result.meta.last_page"
            />
        </template>
    </section>
</template>
