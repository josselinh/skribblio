<script setup lang="ts">
import { computed, useTemplateRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { fetchWordGroupSearchResults } from '@/api/search';
import AppIcon from '@/components/AppIcon.vue';
import AppLayout from '@/components/AppLayout.vue';
import CardListSection from '@/components/CardListSection.vue';
import WordGroupCard from '@/components/WordGroupCard.vue';
import { usePageTitle } from '@/composables/usePageTitle';
import { usePaginatedList } from '@/composables/usePaginatedList';
import { primaryButtonClass, secondaryButtonClass } from '@/lib/buttons';
import { preferredContentLanguage } from '@/lib/contentLanguage';
import { intlLocale } from '@/lib/i18n';
import { scrollToSection } from '@/lib/scroll';
import { SKRIBBL_MAX_CHARACTERS, SKRIBBL_MAX_WORD_LENGTH, SKRIBBL_MIN_WORDS } from '@/lib/skribbl';
import { useAuthStore } from '@/stores/auth';

/**
 * Home page, for visitors and logged-in users alike: what the site does, how it works, the latest groups shared in
 * the language of the lists (`preferredContentLanguage()`), then the questions people ask before using it, also
 * read by search engines. The header already has the links to log in and register.
 */
const PREVIEW_SIZE = 6;
const STEPS = ['find', 'together', 'play'] as const;
const QUESTIONS = ['customWords', 'limits', 'account', 'together', 'free', 'languages'] as const;

const { t } = useI18n();
const auth = useAuthStore();
usePageTitle(null);

/** Reloaded when the visitor switches the interface language, which changes the language of the lists. */
const language = computed(() => preferredContentLanguage());
/** What others share, as in the Explorer's catalogue: a logged-in user's own groups, private ones included, are left out. */
const author = computed(() => (auth.isVerified ? 'others' : 'all'));
const latestWordGroups = usePaginatedList(
    () =>
        fetchWordGroupSearchResults({
            q: '',
            author: author.value,
            language: language.value,
            sort: 'recent',
            page: 1,
            perPage: PREVIEW_SIZE,
        }),
    'page',
    [language, author],
);

const seeAllWordGroups = computed(() => {
    const total = latestWordGroups.result.value?.meta.total;

    return total !== undefined && total > PREVIEW_SIZE
        ? { label: t('search.seeAll.wordGroups', { count: total }), to: { name: 'explore', query: { type: 'word-groups' } } }
        : undefined;
});

/** Skribbl.io's limits, quoted by the answers. */
const limits = computed(() => {
    const format = (value: number) => new Intl.NumberFormat(intlLocale()).format(value);

    return { min: format(SKRIBBL_MIN_WORDS), length: format(SKRIBBL_MAX_WORD_LENGTH), max: format(SKRIBBL_MAX_CHARACTERS) };
});

const howItWorks = useTemplateRef<HTMLElement>('how-it-works');
</script>

<template>
    <AppLayout>
        <section class="py-6 text-center sm:py-12">
            <h1 class="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ $t('home.hero.title') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-pretty text-zinc-600 dark:text-zinc-400">{{ $t('home.hero.subtitle') }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <RouterLink :to="{ name: 'explore' }" :class="primaryButtonClass">
                    <AppIcon name="search" />
                    {{ $t('home.hero.explore') }}
                </RouterLink>
                <button type="button" :class="secondaryButtonClass" @click="howItWorks && scrollToSection(howItWorks)">
                    {{ $t('home.hero.howItWorks') }}
                </button>
            </div>
        </section>

        <section ref="how-it-works" aria-labelledby="how-it-works-title" class="mt-8 scroll-mt-8">
            <h2 id="how-it-works-title" class="text-xl font-semibold">{{ $t('home.steps.title') }}</h2>
            <ol class="mt-4 grid gap-4 sm:grid-cols-3">
                <li
                    v-for="(step, index) in STEPS"
                    :key="step"
                    class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-xs"
                >
                    <span
                        aria-hidden="true"
                        class="flex size-8 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-950 font-semibold text-brand-700 dark:text-brand-300"
                    >
                        {{ index + 1 }}
                    </span>
                    <h3 class="mt-3 font-semibold">{{ $t(`home.steps.${step}.title`) }}</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $t(`home.steps.${step}.description`) }}</p>
                </li>
            </ol>
        </section>

        <CardListSection
            class="mt-12"
            section-id="latest-word-groups"
            :title="$t('home.latest.title')"
            :result="latestWordGroups.result.value"
            :is-loading="latestWordGroups.isLoading.value"
            :has-failed="latestWordGroups.hasFailed.value"
            :empty-message="$t('home.latest.empty')"
            :see-all="seeAllWordGroups"
            :paginated="false"
            @retry="latestWordGroups.reload"
        >
            <template #item="{ item }">
                <WordGroupCard :word-group="item" />
            </template>
        </CardListSection>

        <section aria-labelledby="faq-title" class="mt-12">
            <h2 id="faq-title" class="text-xl font-semibold">{{ $t('home.faq.title') }}</h2>
            <div
                class="-mx-4 mt-4 divide-y divide-zinc-200 dark:divide-zinc-800 border-y border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs sm:mx-0 sm:rounded-xl sm:border"
            >
                <details v-for="question in QUESTIONS" :key="question" class="group px-4 py-4 sm:px-6">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium [&::-webkit-details-marker]:hidden">
                        {{ $t(`home.faq.${question}.question`) }}
                        <AppIcon name="arrow-down" class="shrink-0 text-zinc-400 dark:text-zinc-500 transition group-open:rotate-180 motion-reduce:transition-none" />
                    </summary>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $t(`home.faq.${question}.answer`, limits) }}</p>
                </details>
            </div>
        </section>
    </AppLayout>
</template>
