<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import AccountNav from '@/components/AccountNav.vue';
import AppLayout from '@/components/AppLayout.vue';
import FormField from '@/components/FormField.vue';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const { showToast } = useToasts();
const { t } = useI18n();

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

async function savePassword(): Promise<void> {
    const isSuccessful = await passwordForm.submit((data) => auth.updatePassword({ ...data }));
    passwordForm.reset();

    if (isSuccessful) {
        showToast(t('profile.password.saved'));
    }
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <h1 class="text-2xl font-semibold">{{ $t('profile.security.title') }}</h1>
            <AccountNav class="mt-4" />

            <section class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm" aria-labelledby="password-title">
                <h2 id="password-title" class="text-lg font-semibold">{{ $t('auth.fields.password') }}</h2>
                <form class="mt-4 space-y-4" novalidate @submit.prevent="savePassword">
                    <FormField
                        id="current-password"
                        v-model="passwordForm.data.current_password"
                        :label="$t('profile.password.current')"
                        type="password"
                        autocomplete="current-password"
                        :error="passwordForm.errors.value.current_password"
                        required
                    />
                    <FormField
                        id="new-password"
                        v-model="passwordForm.data.password"
                        :label="$t('auth.fields.newPassword')"
                        type="password"
                        autocomplete="new-password"
                        :error="passwordForm.errors.value.password"
                        required
                    />
                    <FormField
                        id="new-password-confirmation"
                        v-model="passwordForm.data.password_confirmation"
                        :label="$t('profile.password.confirmation')"
                        type="password"
                        autocomplete="new-password"
                        :error="passwordForm.errors.value.password_confirmation"
                        required
                    />
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="passwordForm.isProcessing.value"
                            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ $t('profile.password.submit') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
