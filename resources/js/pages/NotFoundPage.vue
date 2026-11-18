<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>

<template>
    <main class="flex min-h-screen flex-col items-center justify-center gap-4 px-4">
        <h1 class="text-2xl font-semibold">Page introuvable</h1>
        <RouterLink :to="{ name: 'dashboard' }" class="text-sm underline">Retour au tableau de bord</RouterLink>
    </main>
</template>
