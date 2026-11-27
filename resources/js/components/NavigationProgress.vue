<script setup lang="ts">
import { isNavigationPending } from '@/lib/navigationProgress';

/**
 * Thin bar at the top of the screen while the router opens a page (`@/lib/navigationProgress`), so that a click
 * whose page takes a moment to load does not look ignored. The same markup, without its label, is the
 * placeholder of `app.blade.php` until the application starts.
 */
</script>

<template>
    <div
        v-if="isNavigationPending"
        role="progressbar"
        :aria-label="$t('common.loading')"
        class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1 overflow-hidden"
    >
        <div class="h-full w-1/3 animate-navigation-progress bg-brand-300 dark:bg-brand-400 motion-reduce:w-full motion-reduce:animate-none"></div>
    </div>
</template>
