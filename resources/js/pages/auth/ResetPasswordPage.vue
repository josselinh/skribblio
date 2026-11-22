<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import AuthCard from '@/components/AuthCard.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import { useForm } from '@/composables/useForm';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const status = ref<string | null>(null);

const form = useForm({
    token: String(route.params.token ?? ''),
    email: typeof route.query.email === 'string' ? route.query.email : '',
    password: '',
    password_confirmation: '',
});

async function submit(): Promise<void> {
    const isSuccessful = await form.submit(async (data) => {
        status.value = await auth.resetPassword(data);
    });

    if (!isSuccessful) {
        form.reset('password', 'password_confirmation');
    }
}
</script>

<template>
    <AuthCard :title="$t('auth.resetPassword.title')">
        <template v-if="status">
            <StatusMessage :message="status" />
            <RouterLink :to="{ name: 'login' }" class="text-sm font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">{{ $t('auth.login.submit') }}</RouterLink>
        </template>

        <form v-else class="space-y-4" novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.data.email"
                :label="$t('auth.fields.email')"
                type="email"
                autocomplete="email"
                :error="form.errors.value.email ?? form.errors.value.token"
                required
            />
            <FormField
                id="password"
                v-model="form.data.password"
                :label="$t('auth.fields.newPassword')"
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
            <PrimaryButton :is-loading="form.isProcessing.value">{{ $t('common.save') }}</PrimaryButton>
        </form>
    </AuthCard>
</template>
