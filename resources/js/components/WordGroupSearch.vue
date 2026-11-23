<script setup lang="ts">
import { searchWordGroups } from '@/api/wordGroups';
import ItemSearch from '@/components/ItemSearch.vue';
import type { Locale, WordGroup } from '@/types/models';

/**
 * Search field over the groups the user can see (their own first, then public ones),
 * with a results dropdown. Emits `select` when a group is clicked.
 */
const props = withDefaults(
    defineProps<{
        inputId: string;
        /** Groups that cannot be picked again (already selected or already in the collection). */
        excludedIds?: string[];
        /** Only offers the groups in this language (e.g. the language of a collection). */
        language?: Locale;
        invalid?: boolean;
        disabled?: boolean;
    }>(),
    { excludedIds: () => [], invalid: false, disabled: false },
);

defineEmits<{
    select: [wordGroup: WordGroup];
}>();

function search(query: string): Promise<WordGroup[]> {
    return searchWordGroups(query, props.language);
}
</script>

<template>
    <ItemSearch
        :input-id="inputId"
        :search="search"
        :placeholder="$t('wordGroups.search.placeholder')"
        :empty-message="$t('wordGroups.search.empty')"
        :excluded-ids="excludedIds"
        :invalid="invalid"
        :disabled="disabled"
        @select="$emit('select', $event)"
    />
</template>
