<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import AppLayout from '@/components/AppLayout.vue';
import FormField from '@/components/FormField.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const { showToast } = useToasts();
const { t } = useI18n();

const profileForm = useForm({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' });

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

/** A new email address must be verified: the user is then sent to the verification page. */
async function saveProfile(): Promise<void> {
    const isSuccessful = await profileForm.submit((data) => auth.updateProfileInformation({ ...data }));

    if (!isSuccessful) {
        return;
    }
    if (!auth.isVerified) {
        await router.push({ name: 'verify-email' });

        return;
    }
    showToast(t('profile.information.saved'));
}

async function savePassword(): Promise<void> {
    const isSuccessful = await passwordForm.submit((data) => auth.updatePassword({ ...data }));
    passwordForm.reset();

    if (isSuccessful) {
        showToast(t('profile.password.saved'));
    }
}

const deletionForm = useForm({ password: '' });
const isConfirmingDeletion = ref(false);
const deletionError = ref<string | null>(null);

function askDeletionConfirmation(): void {
    deletionForm.reset();
    deletionForm.errors.value = {};
    deletionError.value = null;
    isConfirmingDeletion.value = true;
}

/** Once deleted, the user is logged out and sent to the login page with a confirmation. */
async function deleteAccount(): Promise<void> {
    deletionError.value = null;
    try {
        const isSuccessful = await deletionForm.submit((data) => auth.deleteAccount(data.password));
        if (!isSuccessful) {
            return;
        }
    } catch {
        deletionError.value = t('profile.deletion.failed');

        return;
    }
    isConfirmingDeletion.value = false;
    await router.push({ name: 'login', state: { status: t('profile.deletion.done') } });
}

const buttonClass =
    'rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60';
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <h1 class="text-2xl font-semibold">{{ $t('profile.title') }}</h1>

            <section class="mt-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm" aria-labelledby="profile-title">
                <h2 id="profile-title" class="text-lg font-semibold">{{ $t('profile.information.title') }}</h2>
                <form class="mt-4 space-y-4" novalidate @submit.prevent="saveProfile">
                    <FormField
                        id="profile-name"
                        v-model="profileForm.data.name"
                        :label="$t('auth.fields.name')"
                        autocomplete="name"
                        :error="profileForm.errors.value.name"
                        required
                    />
                    <div>
                        <FormField
                            id="profile-email"
                            v-model="profileForm.data.email"
                            :label="$t('auth.fields.email')"
                            type="email"
                            autocomplete="email"
                            :error="profileForm.errors.value.email"
                            required
                        />
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $t('profile.information.emailHint') }}
                        </p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="profileForm.isProcessing.value" :class="buttonClass">{{ $t('common.save') }}</button>
                    </div>
                </form>
            </section>

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
                        <button type="submit" :disabled="passwordForm.isProcessing.value" :class="buttonClass">
                            {{ $t('profile.password.submit') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="mt-6 rounded-xl border border-red-200 dark:border-red-900 bg-white dark:bg-zinc-900 p-6 shadow-sm" aria-labelledby="delete-account-title">
                <h2 id="delete-account-title" class="text-lg font-semibold">{{ $t('profile.deletion.title') }}</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $t('profile.deletion.description') }}
                </p>
                <div class="mt-4 flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg border border-red-200 dark:border-red-900 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40"
                        @click="askDeletionConfirmation"
                    >
                        {{ $t('profile.deletion.title') }}
                    </button>
                </div>
            </section>
        </div>

        <ModalDialog
            v-model:open="isConfirmingDeletion"
            :title="$t('profile.deletion.confirmTitle')"
            role="alertdialog"
            :is-dismissible="!deletionForm.isProcessing.value"
        >
            <p>
                {{ $t('profile.deletion.confirmMessage') }}
            </p>
            <form id="delete-account-form" class="mt-4" novalidate @submit.prevent="deleteAccount">
                <FormField
                    id="delete-account-password"
                    v-model="deletionForm.data.password"
                    :label="$t('profile.deletion.password')"
                    type="password"
                    autocomplete="current-password"
                    :error="deletionForm.errors.value.password"
                    required
                />
            </form>
            <p v-if="deletionError" role="alert" class="mt-3 text-red-600 dark:text-red-400">{{ deletionError }}</p>

            <template #actions>
                <button
                    type="button"
                    :disabled="deletionForm.isProcessing.value"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60 sm:w-auto sm:py-2"
                    @click="isConfirmingDeletion = false"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    form="delete-account-form"
                    :disabled="deletionForm.isProcessing.value"
                    class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 sm:w-auto sm:py-2"
                >
                    {{ deletionForm.isProcessing.value ? $t('deletion.processing') : $t('deletion.confirm') }}
                </button>
            </template>
        </ModalDialog>
    </AppLayout>
</template>
