<script setup lang="ts">
import { computed } from 'vue';
import UserLink from '@/components/UserLink.vue';
import { formatDateTime } from '@/lib/format';
import { intlLocale } from '@/lib/i18n';
import type { User } from '@/types/models';

/** "par Bob · 42 mots · modifié le …" line of a card; the creator's name links to their page. */
const props = defineProps<{
    creator?: Pick<User, 'id' | 'name'>;
    /** Counted items, e.g. "42 mots". */
    countLabel?: string;
    updatedAt?: string | null;
    /** "Modifié le", lowercased when it does not start the line. */
    updatedLabel?: string;
}>();

const updatedText = computed(() => {
    const label = props.updatedLabel ?? '';

    return props.creator || props.countLabel ? label.charAt(0).toLocaleLowerCase(intlLocale()) + label.slice(1) : label;
});
</script>

<template>
    <!-- Above the card's stretched link so that the creator's name stays clickable. -->
    <p v-if="creator || countLabel || updatedAt" class="relative z-10 text-sm text-zinc-600 dark:text-zinc-400">
        <i18n-t v-if="creator" keypath="common.byUser" scope="global">
            <template #user><UserLink :user="creator" /></template>
        </i18n-t>
        <template v-if="creator && countLabel"> · </template>
        <template v-if="countLabel">{{ countLabel }}</template>
        <template v-if="updatedAt">
            <template v-if="creator || countLabel"> · </template>
            <span class="text-zinc-500 dark:text-zinc-400">{{ updatedText }} <time :datetime="updatedAt">{{ formatDateTime(updatedAt) }}</time></span>
        </template>
    </p>
</template>
