<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const searchQuery = ref('');

async function search(): Promise<void> {
    await router.push({ name: 'search', query: { q: searchQuery.value.trim() || undefined } });
    searchQuery.value = '';
}

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-3">
                <RouterLink :to="{ name: 'dashboard' }" class="font-semibold tracking-tight">Skribblio Tools</RouterLink>
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
                        class="block w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-1.5 text-sm focus:border-zinc-900 focus:bg-white focus:ring-1 focus:ring-zinc-900 focus:outline-none"
                    />
                </form>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-zinc-500">{{ auth.user?.name }}</span>
                    <button type="button" class="underline hover:text-zinc-900" @click="logout">Se déconnecter</button>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
