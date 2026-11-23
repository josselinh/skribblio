<script setup lang="ts">
import { RouterLink } from 'vue-router';
import type { User } from '@/types/models';

/** A user's name linking to their page, the current user's included (their page as the others see it). */
defineProps<{
    user: Pick<User, 'id' | 'name'>;
}>();
</script>

<template>
    <RouterLink :to="{ name: 'users.show', params: { id: user.id } }" class="underline-offset-2 hover:text-brand-700 dark:hover:text-brand-300 hover:underline">
        {{ user.name }}
    </RouterLink>
</template>
