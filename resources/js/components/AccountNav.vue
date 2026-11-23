<script setup lang="ts">
import { RouterLink } from 'vue-router';

/** Tabs between the pages of the account (profile, security, preferences), also reachable from `UserMenu`. */
const tabs = [
    { name: 'profile', label: 'profile.nav.profile' },
    { name: 'security', label: 'profile.nav.security' },
    { name: 'preferences', label: 'profile.nav.preferences' },
] as const;
</script>

<template>
    <nav :aria-label="$t('profile.nav.label')" class="relative overflow-x-auto">
        <ul class="flex gap-1 border-b border-zinc-200 dark:border-zinc-800">
            <li v-for="tab in tabs" :key="tab.name">
                <RouterLink
                    :to="{ name: tab.name }"
                    class="-mb-px block border-b-2 border-transparent px-3 py-2 text-sm whitespace-nowrap text-zinc-600 dark:text-zinc-400 hover:text-brand-700 dark:hover:text-brand-300 aria-[current=page]:border-brand-600 dark:aria-[current=page]:border-brand-400 aria-[current=page]:font-medium aria-[current=page]:text-brand-700 dark:aria-[current=page]:text-brand-300"
                >
                    {{ $t(tab.label) }}
                </RouterLink>
            </li>
        </ul>
    </nav>
</template>
