<script setup lang="ts">
import { computed } from 'vue';
import { creatorLabel } from '@/lib/creator';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types/models';

/** "par Bob · 42 mots" line of a card, shown in search results. */
const props = defineProps<{
    creator?: Pick<User, 'id' | 'name'>;
    count?: number;
    /** Singular and plural label of the counted items, e.g. ['mot', 'mots']. */
    countLabels: [string, string];
}>();

const auth = useAuthStore();

const parts = computed(() =>
    [
        props.creator ? `par ${creatorLabel(props.creator, auth.user?.id)}` : null,
        props.count !== undefined ? `${props.count} ${props.count > 1 ? props.countLabels[1] : props.countLabels[0]}` : null,
    ].filter((part): part is string => part !== null),
);
</script>

<template>
    <p v-if="parts.length > 0" class="text-sm text-zinc-600">{{ parts.join(' · ') }}</p>
</template>
