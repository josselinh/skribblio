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
    <nav v-if="lastPage > 1" aria-label="Pagination" class="flex items-center justify-between gap-4 text-sm">
        <RouterLink
            v-if="currentPage > 1"
            :to="pageLocation(currentPage - 1)"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 hover:bg-zinc-50"
        >
            ← Précédent
        </RouterLink>
        <span v-else />
        <span class="text-zinc-500">Page {{ currentPage }} sur {{ lastPage }}</span>
        <RouterLink
            v-if="currentPage < lastPage"
            :to="pageLocation(currentPage + 1)"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 hover:bg-zinc-50"
        >
            Suivant →
        </RouterLink>
        <span v-else />
    </nav>
</template>
