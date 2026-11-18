import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import type { PaginatedResponse } from '@/types/api';

/**
 * Loads a paginated API list whose current page lives in the URL query (`?{pageParam}=N`),
 * and reloads it whenever that page changes.
 */
export function usePaginatedList<T>(fetchPage: (page: number) => Promise<PaginatedResponse<T>>, pageParam = 'page') {
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

    watch(currentPage, reload, { immediate: true });

    return { result, isLoading, hasFailed, currentPage, reload };
}
