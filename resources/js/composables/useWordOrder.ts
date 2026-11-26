import { computed, toValue, type ComputedRef, type MaybeRefOrGetter } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import type { WordOrder } from '@/api/words';
import type { SortDirection, WordSort } from '@/types/models';

/** Direction applied when choosing each criterion: alphabetical for text, most recent or best first otherwise. */
export const WORD_SORT_DEFAULT_DIRECTIONS: Record<WordSort, SortDirection> = {
    name: 'asc',
    author: 'asc',
    created: 'desc',
    updated: 'desc',
    score: 'desc',
};

const DEFAULT_ORDER: WordOrder = { sort: 'created', direction: 'desc' };

/**
 * Order of the words of a group, kept in the URL (`?sort=…&direction=…`) except the default one (most recently added
 * first). `unavailableSorts` (sorts a visitor may not use) fall back to the default; changing the order goes back to
 * the first page.
 */
export function useWordOrder(unavailableSorts: MaybeRefOrGetter<WordSort[]> = []): {
    order: ComputedRef<WordOrder>;
    sortWords: (sort: WordSort, direction: SortDirection) => Promise<void>;
} {
    const route = useRoute();
    const router = useRouter();

    const order = computed<WordOrder>(() => {
        const sort = route.query.sort;
        if (typeof sort !== 'string' || !(sort in WORD_SORT_DEFAULT_DIRECTIONS) || toValue(unavailableSorts).includes(sort as WordSort)) {
            return DEFAULT_ORDER;
        }
        const direction = route.query.direction;

        return {
            sort: sort as WordSort,
            direction: direction === 'asc' || direction === 'desc' ? direction : WORD_SORT_DEFAULT_DIRECTIONS[sort as WordSort],
        };
    });

    async function sortWords(sort: WordSort, direction: SortDirection): Promise<void> {
        const isDefault = sort === DEFAULT_ORDER.sort && direction === DEFAULT_ORDER.direction;
        await router.push({
            query: {
                ...route.query,
                page: undefined,
                sort: isDefault ? undefined : sort,
                direction: isDefault || direction === WORD_SORT_DEFAULT_DIRECTIONS[sort] ? undefined : direction,
            },
        });
    }

    return { order, sortWords };
}
