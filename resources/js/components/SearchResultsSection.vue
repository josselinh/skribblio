<script setup lang="ts" generic="T extends { id: string }">
import { RouterLink, type RouteLocationRaw } from 'vue-router';
import PaginationNav from '@/components/PaginationNav.vue';
import type { PaginatedResponse } from '@/types/api';

/**
 * One section of search results (groups or collections): title with the total, cards rendered
 * through the `item` slot, then either a "see all" link (preview) or the pagination.
 */
defineProps<{
    sectionId: string;
    title: string;
    result: PaginatedResponse<T> | null;
    isLoading: boolean;
    hasFailed: boolean;
    emptyMessage: string;
    /** Shown in preview mode when there are more results than displayed. */
    seeAll?: { label: string; to: RouteLocationRaw };
    paginated: boolean;
}>();

defineEmits<{
    retry: [];
}>();

defineSlots<{
    item(props: { item: T }): unknown;
}>();
</script>

<template>
    <section :aria-labelledby="`${sectionId}-title`">
        <h2 :id="`${sectionId}-title`" class="text-lg font-semibold">
            {{ title }}
            <span v-if="result" class="font-normal text-zinc-500">({{ result.meta.total }})</span>
        </h2>

        <p v-if="isLoading && !result" class="mt-4 text-zinc-500">Chargement…</p>
        <p v-else-if="hasFailed" class="mt-4 text-red-600">
            Impossible de charger les résultats.
            <button type="button" class="underline" @click="$emit('retry')">Réessayer</button>
        </p>
        <p v-else-if="result?.data.length === 0" class="mt-4 text-zinc-500">{{ emptyMessage }}</p>
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
                :current-page="result.meta.current_page"
                :last-page="result.meta.last_page"
            />
        </template>
    </section>
</template>
