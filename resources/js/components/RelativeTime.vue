<script setup lang="ts">
import { computed } from 'vue';
import { useNow } from '@/composables/useNow';
import { formatDateTime, formatRelativeTime } from '@/lib/format';
import { intlLocale } from '@/lib/i18n';

/**
 * A date written relatively to now ("il y a 3 jours", "hier", "le 18 nov. 2026"), with the exact date and time
 * on hover. `capitalized` when it starts a sentence.
 */
const props = defineProps<{
    date: string;
    capitalized?: boolean;
}>();

const now = useNow();

const text = computed(() => {
    const relative = formatRelativeTime(props.date, now.value);

    return props.capitalized ? relative.charAt(0).toLocaleUpperCase(intlLocale()) + relative.slice(1) : relative;
});
</script>

<template>
    <time :datetime="date" :title="formatDateTime(date)">{{ text }}</time>
</template>
