<script setup lang="ts">
const repositoryUrl = 'https://github.com/josselinh/skribblio';
</script>

<template>
    <footer class="px-4 py-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <p class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
            <span>Projet open source sous licence MIT</span>
            <span aria-hidden="true">·</span>
            <a :href="repositoryUrl" target="_blank" rel="noopener" class="underline-offset-2 hover:text-zinc-900 dark:hover:text-zinc-100 hover:underline">
                Code source sur GitHub
            </a>
            <span aria-hidden="true">·</span>
            <a
                :href="`${repositoryUrl}/issues`"
                target="_blank"
                rel="noopener"
                class="underline-offset-2 hover:text-zinc-900 dark:hover:text-zinc-100 hover:underline"
            >
                Signaler un problème
            </a>
        </p>
        <p class="mt-1">Projet indépendant, non affilié à Skribbl.io.</p>
    </footer>
</template>
