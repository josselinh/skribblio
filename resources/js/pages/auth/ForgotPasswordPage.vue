<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import AuthCard from '@/components/AuthCard.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import { useForm } from '@/composables/useForm';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const status = ref<string | null>(null);

const form = useForm({ email: '' });

async function submit(): Promise<void> {
    status.value = null;
    await form.submit(async (data) => {
        status.value = await auth.sendPasswordResetLink(data.email);
    });
}
</script>

<template>
    <AuthCard
        :title="$t('auth.forgotPassword.title')"
        :description="$t('auth.forgotPassword.description')"
    >
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
            <PrimaryButton :is-loading="form.isProcessing.value">{{ $t('auth.forgotPassword.submit') }}</PrimaryButton>
        </form>

        <template #footer>
            <RouterLink :to="{ name: 'login' }" class="font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">{{ $t('auth.forgotPassword.backToLogin') }}</RouterLink>
        </template>
    </AuthCard>
</template>
