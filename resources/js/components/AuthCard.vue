<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <p class="mb-8 text-center text-lg font-semibold tracking-tight">Skribblio Tools</p>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h1 class="text-xl font-semibold">{{ title }}</h1>
                <p v-if="description" class="mt-1 text-sm text-zinc-500">{{ description }}</p>
                <div class="mt-6">
                    <slot />
                </div>
            </div>
            <div class="mt-6 text-center text-sm text-zinc-500">
                <slot name="footer" />
            </div>
        </div>
    </main>
</template>
