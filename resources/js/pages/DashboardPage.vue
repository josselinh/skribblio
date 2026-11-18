<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import StatusMessage from '@/components/StatusMessage.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const status = computed(() => (route.query.verified === '1' ? 'Votre adresse email est confirmée, bienvenue !' : null));

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                <span class="font-semibold tracking-tight">Skribblio Tools</span>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-zinc-500">{{ auth.user?.name }}</span>
                    <button type="button" class="underline hover:text-zinc-900" @click="logout">Se déconnecter</button>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-8">
            <StatusMessage :message="status" />
            <h1 class="text-2xl font-semibold">Tableau de bord</h1>
            <p class="mt-2 text-zinc-500">Aucune liste pour le moment.</p>
        </main>
    </div>
</template>
