<script setup lang="ts">
import { computed } from 'vue';
import UserLink from '@/components/UserLink.vue';
import type { User } from '@/types/models';

/** "par Bob · 42 mots" line of a card; the creator's name links to their page. */
const props = defineProps<{
    creator?: Pick<User, 'id' | 'name'>;
    count?: number;
    /** Singular and plural label of the counted items, e.g. ['mot', 'mots']. */
    countLabels: [string, string];
}>();

const countLabel = computed(() =>
    props.count === undefined ? null : `${props.count} ${props.count > 1 ? props.countLabels[1] : props.countLabels[0]}`,
);
</script>

<template>
    <!-- Above the card's stretched link so that the creator's name stays clickable. -->
    <p v-if="creator || countLabel" class="relative z-10 text-sm text-zinc-600">
        <template v-if="creator">par <UserLink :user="creator" self-label="vous" /></template>
        <template v-if="creator && countLabel"> · </template>
        <template v-if="countLabel">{{ countLabel }}</template>
    </p>
</template>
