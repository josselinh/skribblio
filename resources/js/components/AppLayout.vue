<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppFooter from '@/components/AppFooter.vue';
import UserMenu from '@/components/UserMenu.vue';

const route = useRoute();
const router = useRouter();

const searchQuery = ref('');

/** Navigation link style, darker for the current page's section. */
const navLinkClass = (routeName: string) =>
    route.name === routeName ? 'text-sm font-medium text-brand-700 dark:text-brand-300' : 'text-sm text-zinc-600 dark:text-zinc-400 hover:text-brand-700 dark:hover:text-brand-300';

async function search(): Promise<void> {
    await router.push({ name: 'search', query: { q: searchQuery.value.trim() || undefined } });
    searchQuery.value = '';
}
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="border-t-4 border-b border-t-brand-600 border-b-zinc-200 dark:border-b-zinc-800 bg-white dark:bg-zinc-900">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-3">
                <nav class="flex items-center gap-x-5" aria-label="Navigation principale">
                    <RouterLink :to="{ name: 'dashboard' }" class="font-semibold tracking-tight text-brand-700 dark:text-brand-300">Skribblio Tools</RouterLink>
                    <RouterLink :to="{ name: 'dashboard' }" :class="navLinkClass('dashboard')">Tableau de bord</RouterLink>
                    <RouterLink :to="{ name: 'search' }" :class="navLinkClass('search')">Explorer</RouterLink>
                </nav>
                <!-- The search page has its own, larger search field. -->
                <form
                    v-if="route.name !== 'search'"
                    role="search"
                    class="order-last w-full sm:order-none sm:w-auto sm:flex-1 sm:max-w-sm"
                    @submit.prevent="search"
                >
                    <label for="header-search" class="sr-only">Rechercher un groupe ou une collection</label>
                    <input
                        id="header-search"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Rechercher un groupe ou une collection…"
                        class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-3 py-1.5 text-sm focus:border-brand-500 dark:focus:border-brand-400 focus:bg-white dark:focus:bg-zinc-900 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none"
                    />
                </form>
                <UserMenu />
            </div>
        </header>
        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
