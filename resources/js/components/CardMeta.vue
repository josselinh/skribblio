<script setup lang="ts">
import UserLink from '@/components/UserLink.vue';
import type { User } from '@/types/models';

/** "par Bob · 42 mots" line of a card; the creator's name links to their page. */
defineProps<{
    creator?: Pick<User, 'id' | 'name'>;
    /** Counted items, e.g. "42 mots". */
    countLabel?: string;
}>();
</script>

<template>
    <!-- Above the card's stretched link so that the creator's name stays clickable. -->
    <p v-if="creator || countLabel" class="relative z-10 text-sm text-zinc-600 dark:text-zinc-400">
        <i18n-t v-if="creator" keypath="common.byUser" scope="global">
            <template #user><UserLink :user="creator" /></template>
        </i18n-t>
        <template v-if="creator && countLabel"> · </template>
        <template v-if="countLabel">{{ countLabel }}</template>
    </p>
</template>
