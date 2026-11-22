<script setup lang="ts">
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AuthCard from '@/components/AuthCard.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import { useForm } from '@/composables/useForm';
import { consumeHistoryStatus } from '@/lib/historyStatus';
import { consumePendingRedirect } from '@/lib/pendingRedirect';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = useForm({ email: '', password: '', remember: false });
/** E.g. the confirmation that the account was deleted. */
const status = consumeHistoryStatus();

/** Only follows internal paths, to avoid open redirects. */
function redirectTarget(): string | { name: string } {
    const redirect = route.query.redirect;

    return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')
        ? redirect
        : { name: 'dashboard' };
}

async function submit(): Promise<void> {
    const isSuccessful = await form.submit((data) => auth.login(data));

    if (isSuccessful) {
        consumePendingRedirect();
        await router.push(redirectTarget());
    } else {
        form.reset('password');
    }
}
</script>

<template>
    <AuthCard :title="$t('auth.login.title')" :description="$t('auth.login.description')">
        <StatusMessage :message="status" />
        <form class="space-y-4" novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.data.email"
                :label="$t('auth.fields.email')"
                type="email"
                autocomplete="email"
                :error="form.errors.value.email"
                required
            />
            <FormField
                id="password"
                v-model="form.data.password"
                :label="$t('auth.fields.password')"
                type="password"
                autocomplete="current-password"
                :error="form.errors.value.password"
                required
            />
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2">
                    <input v-model="form.data.remember" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-700" />
                    {{ $t('auth.login.remember') }}
                </label>
                <RouterLink :to="{ name: 'forgot-password' }" class="text-zinc-600 dark:text-zinc-400 underline hover:text-zinc-900 dark:hover:text-zinc-100">
                    {{ $t('auth.login.forgotPassword') }}
                </RouterLink>
            </div>
            <PrimaryButton :is-loading="form.isProcessing.value">{{ $t('auth.login.submit') }}</PrimaryButton>
        </form>

        <template #footer>
            {{ $t('auth.login.noAccount') }}
            <RouterLink :to="{ name: 'register' }" class="font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">{{ $t('auth.login.register') }}</RouterLink>
        </template>
    </AuthCard>
</template>
