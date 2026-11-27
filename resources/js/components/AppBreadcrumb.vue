<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { breadcrumbSteps, currentBreadcrumbLabel, type Crumb } from '@/lib/breadcrumb';

/**
 * Breadcrumb of the current page (`@/lib/breadcrumb`), at the top of `AppLayout`: every step from the dashboard
 * or the Explorer, then the current page. Phones only get a link back to the previous step.
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
const previousStep = computed(() => steps.value.at(-1) ?? null);
const currentLabel = computed(() => currentBreadcrumbLabel());

const linkClass = 'truncate text-brand-700 dark:text-brand-300 hover:underline';
</script>

<template>
    <nav v-if="previousStep" :aria-label="$t('layout.breadcrumb.label')" class="mb-4 text-sm">
        <RouterLink :to="previousStep.crumb.to" :class="linkClass" class="inline-block max-w-full font-medium sm:hidden">
            ← {{ previousStep.label }}
        </RouterLink>
        <ol class="hidden min-w-0 items-center gap-x-2 sm:flex">
            <li v-for="(step, index) in steps" :key="index" class="flex min-w-0 items-center gap-x-2">
                <RouterLink :to="step.crumb.to" :class="linkClass" class="max-w-48">{{ step.label }}</RouterLink>
                <span aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">›</span>
            </li>
            <li v-if="currentLabel" class="min-w-0">
                <span aria-current="page" class="block truncate text-zinc-500 dark:text-zinc-400">{{ currentLabel }}</span>
            </li>
        </ol>
    </nav>
</template>
