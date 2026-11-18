import { computed, ref, watch, type WatchSource } from 'vue';
import { useRoute } from 'vue-router';
import type { PaginatedResponse } from '@/types/api';

/**
 * Loads a paginated API list whose current page lives in the URL query (`?{pageParam}=N`),
 * and reloads it whenever that page or one of the extra `sources` (e.g. search filters) changes.
 * `fetchPage` may return null when the list should not be shown.
 */
export function usePaginatedList<T>(
    fetchPage: (page: number) => Promise<PaginatedResponse<T> | null>,
    pageParam = 'page',
    sources: WatchSource[] = [],
) {
    const route = useRoute();

    const result = ref<PaginatedResponse<T> | null>(null);
    const isLoading = ref(true);
    const hasFailed = ref(false);

    const currentPage = computed(() => {
        const page = Number(route.query[pageParam]);

        return Number.isInteger(page) && page > 0 ? page : 1;
    });

    async function reload(): Promise<void> {
        isLoading.value = true;
        hasFailed.value = false;
        try {
            result.value = (await fetchPage(currentPage.value)) as typeof result.value;
        } catch {
            hasFailed.value = true;
        } finally {
            isLoading.value = false;
        }
    }

    watch([currentPage, ...sources], reload, { immediate: true });

    return { result, isLoading, hasFailed, currentPage, reload };
}
