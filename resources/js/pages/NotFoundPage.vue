<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { usePageTitle } from '@/composables/usePageTitle';

const { t } = useI18n();
usePageTitle(() => t('layout.notFound.title'));
</script>

<template>
    <main class="flex min-h-screen flex-col items-center justify-center gap-4 px-4">
        <h1 class="text-2xl font-semibold">{{ $t('layout.notFound.title') }}</h1>
        <RouterLink :to="{ name: 'home' }" class="text-sm text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">{{ $t('layout.notFound.backHome') }}</RouterLink>
    </main>
</template>
