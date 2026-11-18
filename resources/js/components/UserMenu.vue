<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

/** Header button with the user's initials and name, opening a menu: profile and logout. */
const auth = useAuthStore();
const router = useRouter();

const isOpen = ref(false);

const initials = computed(() =>
    (auth.user?.name ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join(''),
);

function close(): void {
    isOpen.value = false;
}

/** Closes the menu when the focus leaves it (click elsewhere, Tab). */
function onFocusOut(event: FocusEvent): void {
    const container = event.currentTarget as HTMLElement;
    if (!container.contains(event.relatedTarget as Node | null)) {
        close();
    }
}

async function logout(): Promise<void> {
    close();
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <div class="relative" @focusout="onFocusOut" @keydown.esc="close">
        <button
            type="button"
            class="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 text-sm hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-zinc-900"
            aria-haspopup="menu"
            :aria-expanded="isOpen"
            aria-controls="user-menu"
            @click="isOpen = !isOpen"
        >
            <span
                class="flex size-7 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold text-white"
                aria-hidden="true"
            >
                {{ initials }}
            </span>
            <span class="max-w-40 truncate font-medium">{{ auth.user?.name }}</span>
            <span class="text-zinc-500" aria-hidden="true">▾</span>
        </button>

        <div
            v-if="isOpen"
            id="user-menu"
            role="menu"
            class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-zinc-200 bg-white py-1 shadow-lg"
        >
            <p class="truncate border-b border-zinc-100 px-4 py-2 text-xs text-zinc-500">{{ auth.user?.email }}</p>
            <RouterLink
                :to="{ name: 'profile' }"
                role="menuitem"
                class="block px-4 py-2 text-sm hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-none"
                @click="close"
            >
                Mon profil
            </RouterLink>
            <button
                type="button"
                role="menuitem"
                class="block w-full px-4 py-2 text-left text-sm hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-none"
                @click="logout"
            >
                Se déconnecter
            </button>
        </div>
    </div>
</template>
