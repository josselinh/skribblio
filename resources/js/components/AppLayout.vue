<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';
import AppBreadcrumb from '@/components/AppBreadcrumb.vue';
import AppFooter from '@/components/AppFooter.vue';
import UserMenu from '@/components/UserMenu.vue';
import { rememberPendingRedirect } from '@/lib/pendingRedirect';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const searchQuery = ref('');

/** Navigation link style, darker for the current page's section. */
const navLinkClass = (routeName: string) =>
    route.name === routeName ? 'text-sm font-medium text-brand-700 dark:text-brand-300' : 'text-sm text-zinc-600 dark:text-zinc-400 hover:text-brand-700 dark:hover:text-brand-300';

/** Registering goes through the email verification, opened in a new tab: the page is remembered until then. */
function rememberCurrentPage(): void {
    rememberPendingRedirect(route.fullPath);
}

async function search(): Promise<void> {
    await router.push({ name: 'explore', query: { q: searchQuery.value.trim() || undefined } });
    searchQuery.value = '';
}
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="app-header border-t-4 border-b border-t-brand-600 border-b-zinc-200 dark:border-b-zinc-800 bg-white dark:bg-zinc-900">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-3">
                <nav class="flex items-center gap-x-5" :aria-label="$t('layout.header.mainNavigation')">
                    <RouterLink :to="{ name: auth.isAuthenticated ? 'dashboard' : 'home' }" class="font-semibold tracking-tight text-brand-700 dark:text-brand-300">{{ $t('common.appName') }}</RouterLink>
                    <RouterLink v-if="auth.isAuthenticated" :to="{ name: 'dashboard' }" :class="navLinkClass('dashboard')">{{ $t('layout.header.dashboard') }}</RouterLink>
                    <RouterLink :to="{ name: 'explore' }" :class="navLinkClass('explore')">{{ $t('layout.header.explore') }}</RouterLink>
                </nav>
                <!-- The Explorer has its own, larger search field. -->
                <form
                    v-if="route.name !== 'explore'"
                    role="search"
                    class="order-last w-full sm:order-none sm:w-auto sm:flex-1 sm:max-w-sm"
                    @submit.prevent="search"
                >
                    <label for="header-search" class="sr-only">{{ $t('layout.header.searchLabel') }}</label>
                    <div class="relative">
                        <AppIcon name="search" class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-zinc-400 dark:text-zinc-500" />
                        <input
                            id="header-search"
                            v-model="searchQuery"
                            type="search"
                            :placeholder="$t('layout.header.searchPlaceholder')"
                            class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 py-1.5 pr-3 pl-8 text-sm focus:border-brand-500 dark:focus:border-brand-400 focus:bg-white dark:focus:bg-zinc-900 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none"
                        />
                    </div>
                </form>
                <!-- Kept on the right when the header wraps on phones, so that its menu, aligned on its right edge, stays on screen. -->
                <UserMenu v-if="auth.isAuthenticated" class="ml-auto" />
                <div v-else class="ml-auto flex items-center gap-3">
                    <RouterLink :to="{ name: 'login', query: { redirect: route.fullPath } }" class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-brand-700 dark:hover:text-brand-300">
                        {{ $t('guest.login') }}
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'register' }"
                        class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
                        @click="rememberCurrentPage"
                    >
                        {{ $t('guest.register') }}
                    </RouterLink>
                </div>
            </div>
        </header>
        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
            <AppBreadcrumb />
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
