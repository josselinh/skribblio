<script setup lang="ts">
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { breadcrumbSteps, currentBreadcrumbLabel, type Crumb } from '@/lib/breadcrumb';

/**
 * Breadcrumb of the current page (`@/lib/breadcrumb`), at the top of `AppLayout`: every step from the dashboard
 * or the Explorer, then the current page. Each step is truncated from `sm`; phones keep the whole names on one line that
 * scrolls horizontally (swiped with a finger), starting at its end, with a fade on the left while its start is hidden.
 */
const { t } = useI18n();

function labelOf(crumb: Crumb): string {
    if (crumb.root === 'dashboard') {
        return t('layout.header.dashboard');
    }
    if (crumb.root === 'explore') {
        return t('layout.header.explore');
    }
    return crumb.label ?? '';
}

const steps = computed(() => breadcrumbSteps().map((crumb) => ({ crumb, label: labelOf(crumb) })));
const currentLabel = computed(() => currentBreadcrumbLabel());

const list = useTemplateRef<HTMLOListElement>('list');
const isStartHidden = ref(false);

function updateStartHidden(): void {
    isStartHidden.value = (list.value?.scrollLeft ?? 0) > 0;
}

/** Shows the end of the trail (the current page) on each new page; does nothing where nothing scrolls. */
watch(
    [steps, currentLabel],
    async () => {
        await nextTick();
        if (list.value) {
            list.value.scrollLeft = list.value.scrollWidth;
            updateStartHidden();
        }
    },
    { immediate: true },
);

const linkClass = 'whitespace-nowrap text-brand-700 dark:text-brand-300 hover:underline sm:truncate';
</script>

<template>
    <nav v-if="steps.length > 0" :aria-label="$t('layout.breadcrumb.label')" class="mb-4 text-sm">
        <!-- `relative`: keeps what is positioned inside the scrolling list within its clipping. The scrollbar is hidden, the fade tells
             that the start is out of view. -->
        <ol
            ref="list"
            class="relative -my-1 flex items-center gap-x-2 overflow-x-auto py-1 [scrollbar-width:none] sm:overflow-x-visible [&::-webkit-scrollbar]:hidden"
            :class="{ '[mask-image:linear-gradient(to_right,transparent,black_2rem)]': isStartHidden }"
            @scroll.passive="updateStartHidden"
        >
            <li v-for="(step, index) in steps" :key="index" class="flex shrink-0 items-center gap-x-2 sm:min-w-0 sm:shrink">
                <RouterLink :to="step.crumb.to" :class="linkClass" class="sm:max-w-48">{{ step.label }}</RouterLink>
                <span aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">›</span>
            </li>
            <li v-if="currentLabel" class="shrink-0 sm:min-w-0 sm:shrink">
                <span aria-current="page" class="block whitespace-nowrap text-zinc-500 dark:text-zinc-400 sm:truncate">{{ currentLabel }}</span>
            </li>
        </ol>
    </nav>
</template>
