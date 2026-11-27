<script setup lang="ts">
import { RouterLink } from 'vue-router';
import AppFooter from '@/components/AppFooter.vue';
import { usePageTitle } from '@/composables/usePageTitle';

const props = defineProps<{
    title: string;
    description?: string;
}>();

/** The card's title is the page's: it names the browser tab of every authentication page. */
usePageTitle(() => props.title);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <main class="flex flex-1 items-center justify-center px-4 py-12">
            <div class="w-full max-w-sm">
                <!-- Leads to the Explorer, open to visitors, like the app name in the header. -->
                <p class="mb-8 text-center text-lg font-semibold tracking-tight">
                    <RouterLink :to="{ name: 'explore' }" class="rounded-sm hover:text-brand-700 dark:hover:text-brand-300 focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400">
                        {{ $t('common.appName') }}
                    </RouterLink>
                </p>
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                    <h1 class="text-xl font-semibold">{{ title }}</h1>
                    <p v-if="description" class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ description }}</p>
                    <div class="mt-6">
                        <slot />
                    </div>
                </div>
                <div class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    <slot name="footer" />
                </div>
            </div>
        </main>
        <AppFooter />
    </div>
</template>
