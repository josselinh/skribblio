<script setup lang="ts">
const repositoryUrl = 'https://github.com/josselinh/skribblio';
</script>

<template>
    <footer class="px-4 py-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <p class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
            <span>{{ $t('layout.footer.license') }}</span>
            <span aria-hidden="true">·</span>
            <a :href="repositoryUrl" target="_blank" rel="noopener" class="underline-offset-2 hover:text-zinc-900 dark:hover:text-zinc-100 hover:underline">
                {{ $t('layout.footer.sourceCode') }}
            </a>
            <span aria-hidden="true">·</span>
            <a
                :href="`${repositoryUrl}/issues`"
                target="_blank"
                rel="noopener"
                class="underline-offset-2 hover:text-zinc-900 dark:hover:text-zinc-100 hover:underline"
            >
                {{ $t('layout.footer.reportIssue') }}
            </a>
        </p>
        <p class="mt-1">{{ $t('layout.footer.notAffiliated') }}</p>
    </footer>
</template>
