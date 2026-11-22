<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router';

const props = withDefaults(
    defineProps<{
        currentPage: number;
        lastPage: number;
        /** Query string parameter holding the page, to have several paginated lists on one page. */
        pageParam?: string;
    }>(),
    { pageParam: 'page' },
);

const route = useRoute();

function pageLocation(page: number) {
    return { query: { ...route.query, [props.pageParam]: page > 1 ? String(page) : undefined } };
}
</script>

<template>
    <nav v-if="lastPage > 1" :aria-label="$t('layout.pagination.label')" class="flex items-center justify-between gap-4 text-sm">
        <RouterLink
            v-if="currentPage > 1"
            :to="pageLocation(currentPage - 1)"
            class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 hover:border-brand-300 dark:hover:border-brand-700 hover:bg-brand-50 dark:hover:bg-brand-950 hover:text-brand-700 dark:hover:text-brand-300"
        >
            {{ $t('layout.pagination.previous') }}
        </RouterLink>
        <span v-else />
        <span class="text-zinc-500 dark:text-zinc-400">{{ $t('layout.pagination.status', { current: currentPage, last: lastPage }) }}</span>
        <RouterLink
            v-if="currentPage < lastPage"
            :to="pageLocation(currentPage + 1)"
            class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 hover:border-brand-300 dark:hover:border-brand-700 hover:bg-brand-50 dark:hover:bg-brand-950 hover:text-brand-700 dark:hover:text-brand-300"
        >
            {{ $t('layout.pagination.next') }}
        </RouterLink>
        <span v-else />
    </nav>
</template>
