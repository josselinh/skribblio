<script setup lang="ts">
import { useLocaleChoice } from '@/composables/useLocaleChoice';
import { LOCALES } from '@/lib/i18n';

const { locale, changeLocale } = useLocaleChoice();

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
        <div class="mt-3 flex items-center justify-center gap-1" role="group" :aria-label="$t('layout.footer.language')">
            <button
                v-for="choice in LOCALES"
                :key="choice"
                type="button"
                :lang="choice"
                :aria-pressed="locale === choice"
                class="rounded-md px-2 py-1 focus-visible:outline-2 focus-visible:outline-brand-600 dark:focus-visible:outline-brand-400"
                :class="
                    locale === choice
                        ? 'bg-brand-50 font-medium text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                        : 'hover:text-zinc-900 dark:hover:text-zinc-100'
                "
                @click="changeLocale(choice)"
            >
                {{ $t(`layout.locales.${choice}`) }}
            </button>
        </div>
    </footer>
</template>
