<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router';
import AuthCard from '@/components/AuthCard.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import { useForm } from '@/composables/useForm';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

async function submit(): Promise<void> {
    const isSuccessful = await form.submit((data) => auth.register(data));

    if (isSuccessful) {
        await router.push({ name: 'verify-email' });
    } else {
        form.reset('password', 'password_confirmation');
    }
}
</script>

<template>
    <AuthCard :title="$t('auth.register.title')" :description="$t('auth.register.description')">
        <form class="space-y-4" novalidate @submit.prevent="submit">
            <FormField
                id="name"
                v-model="form.data.name"
                :label="$t('auth.fields.name')"
                autocomplete="name"
                :error="form.errors.value.name"
                required
            />
            <FormField
                id="email"
                v-model="form.data.email"
                :label="$t('auth.fields.email')"
                type="email"
                autocomplete="email"
                :hint="$t('auth.fields.emailHint')"
                :error="form.errors.value.email"
                required
            />
            <FormField
                id="password"
                v-model="form.data.password"
                :label="$t('auth.fields.password')"
                type="password"
                autocomplete="new-password"
                :error="form.errors.value.password"
                required
            />
            <FormField
                id="password_confirmation"
                v-model="form.data.password_confirmation"
                :label="$t('auth.fields.passwordConfirmation')"
                type="password"
                autocomplete="new-password"
                :error="form.errors.value.password_confirmation"
                required
            />
            <PrimaryButton :is-loading="form.isProcessing.value">{{ $t('auth.register.submit') }}</PrimaryButton>
        </form>

        <template #footer>
            {{ $t('auth.register.alreadyRegistered') }}
            <RouterLink :to="{ name: 'login' }" class="font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">{{ $t('auth.login.submit') }}</RouterLink>
        </template>
    </AuthCard>
</template>
