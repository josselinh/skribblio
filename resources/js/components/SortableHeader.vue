<script setup lang="ts" generic="TColumn extends string">
import { computed } from 'vue';
import type { SortDirection } from '@/types/models';

/**
 * Table header cell whose title sorts the table by its column: the first click sorts in the column's
 * natural direction, the next ones reverse it.
 */
const props = defineProps<{
    label: string;
    column: TColumn;
    sort: TColumn;
    direction: SortDirection;
    /** Direction of the first click on this column. */
    defaultDirection: SortDirection;
}>();

const emit = defineEmits<{
    sort: [column: TColumn, direction: SortDirection];
}>();

const isActive = computed(() => props.sort === props.column);

function onClick(): void {
    if (!isActive.value) {
        emit('sort', props.column, props.defaultDirection);
    } else {
        emit('sort', props.column, props.direction === 'asc' ? 'desc' : 'asc');
    }
}
</script>

<template>
    <th
        scope="col"
        class="px-4 py-2.5"
        :aria-sort="isActive ? (direction === 'asc' ? 'ascending' : 'descending') : undefined"
    >
        <button
            type="button"
            class="inline-flex items-center gap-1 tracking-wide uppercase hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-zinc-900"
            :class="{ 'text-zinc-900': isActive }"
            @click="onClick"
        >
            {{ label }}
            <span aria-hidden="true" class="text-[0.65rem]" :class="{ 'opacity-0': !isActive }">
                {{ isActive && direction === 'asc' ? '▲' : '▼' }}
            </span>
        </button>
    </th>
</template>
