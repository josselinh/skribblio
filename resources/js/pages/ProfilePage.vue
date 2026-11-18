<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import AppLayout from '@/components/AppLayout.vue';
import FormField from '@/components/FormField.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import { useForm } from '@/composables/useForm';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const profileForm = useForm({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' });
const profileStatus = ref<string | null>(null);

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const passwordStatus = ref<string | null>(null);

/** A new email address must be verified: the user is then sent to the verification page. */
async function saveProfile(): Promise<void> {
    profileStatus.value = null;
    const isSuccessful = await profileForm.submit((data) => auth.updateProfileInformation({ ...data }));

    if (!isSuccessful) {
        return;
    }
    if (!auth.isVerified) {
        await router.push({ name: 'verify-email' });

        return;
    }
    profileStatus.value = 'Votre profil a été mis à jour.';
}

async function savePassword(): Promise<void> {
    passwordStatus.value = null;
    const isSuccessful = await passwordForm.submit((data) => auth.updatePassword({ ...data }));
    passwordForm.reset();

    if (isSuccessful) {
        passwordStatus.value = 'Votre mot de passe a été modifié.';
    }
}

const buttonClass =
    'rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60';
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
                ← Retour au tableau de bord
            </RouterLink>
            <h1 class="mt-2 text-2xl font-semibold">Mon profil</h1>

            <section class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm" aria-labelledby="profile-title">
                <h2 id="profile-title" class="text-lg font-semibold">Informations</h2>
                <StatusMessage class="mt-4" :message="profileStatus" />
                <form class="mt-4 space-y-4" novalidate @submit.prevent="saveProfile">
                    <FormField
                        id="profile-name"
                        v-model="profileForm.data.name"
                        label="Nom"
                        autocomplete="name"
                        :error="profileForm.errors.value.name"
                        required
                    />
                    <div>
                        <FormField
                            id="profile-email"
                            v-model="profileForm.data.email"
                            label="Adresse email"
                            type="email"
                            autocomplete="email"
                            :error="profileForm.errors.value.email"
                            required
                        />
                        <p class="mt-1 text-sm text-zinc-500">
                            Si vous la modifiez, vous devrez confirmer la nouvelle adresse avant de continuer.
                        </p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="profileForm.isProcessing.value" :class="buttonClass">Enregistrer</button>
                    </div>
                </form>
            </section>

            <section class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm" aria-labelledby="password-title">
                <h2 id="password-title" class="text-lg font-semibold">Mot de passe</h2>
                <StatusMessage class="mt-4" :message="passwordStatus" />
                <form class="mt-4 space-y-4" novalidate @submit.prevent="savePassword">
                    <FormField
                        id="current-password"
                        v-model="passwordForm.data.current_password"
                        label="Mot de passe actuel"
                        type="password"
                        autocomplete="current-password"
                        :error="passwordForm.errors.value.current_password"
                        required
                    />
                    <FormField
                        id="new-password"
                        v-model="passwordForm.data.password"
                        label="Nouveau mot de passe"
                        type="password"
                        autocomplete="new-password"
                        :error="passwordForm.errors.value.password"
                        required
                    />
                    <FormField
                        id="new-password-confirmation"
                        v-model="passwordForm.data.password_confirmation"
                        label="Confirmation du nouveau mot de passe"
                        type="password"
                        autocomplete="new-password"
                        :error="passwordForm.errors.value.password_confirmation"
                        required
                    />
                    <div class="flex justify-end">
                        <button type="submit" :disabled="passwordForm.isProcessing.value" :class="buttonClass">
                            Modifier le mot de passe
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
