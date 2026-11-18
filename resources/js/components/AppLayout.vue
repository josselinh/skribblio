<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                <RouterLink :to="{ name: 'dashboard' }" class="font-semibold tracking-tight">Skribblio Tools</RouterLink>
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
