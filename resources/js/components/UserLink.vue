<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types/models';

/**
 * A user's name linking to their page. The current user's own name is plain text
 * (or `selfLabel`, e.g. "vous").
 */
const props = defineProps<{
    user: Pick<User, 'id' | 'name'>;
    selfLabel?: string;
}>();

const auth = useAuthStore();

const isCurrentUser = computed(() => props.user.id === auth.user?.id);
</script>

<template>
    <span v-if="isCurrentUser">{{ selfLabel ?? user.name }}</span>
    <RouterLink v-else :to="{ name: 'users.show', params: { id: user.id } }" class="underline-offset-2 hover:underline">
        {{ user.name }}
    </RouterLink>
</template>
