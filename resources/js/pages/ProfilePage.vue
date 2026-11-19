<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import AppLayout from '@/components/AppLayout.vue';
import FormField from '@/components/FormField.vue';
import ModalDialog from '@/components/ModalDialog.vue';
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
        deletionError.value = 'Impossible de supprimer votre compte. Réessayez.';

        return;
    }
    isConfirmingDeletion.value = false;
    await router.push({ name: 'login', state: { status: 'Votre compte a été supprimé.' } });
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

            <section class="mt-6 rounded-xl border border-red-200 bg-white p-6 shadow-sm" aria-labelledby="delete-account-title">
                <h2 id="delete-account-title" class="text-lg font-semibold">Supprimer mon compte</h2>
                <p class="mt-2 text-sm text-zinc-600">
                    Vos groupes, leurs mots, vos collections et vos favoris seront supprimés définitivement. Les mots que vous avez
                    ajoutés aux groupes d’autres personnes y resteront, sans votre nom.
                </p>
                <div class="mt-4 flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                        @click="askDeletionConfirmation"
                    >
                        Supprimer mon compte
                    </button>
                </div>
            </section>
        </div>

        <ModalDialog
            v-model:open="isConfirmingDeletion"
            title="Supprimer votre compte ?"
            role="alertdialog"
            :is-dismissible="!deletionForm.isProcessing.value"
        >
            <p>
                Tout ce que vous avez créé sera supprimé et les personnes avec qui vous l’avez partagé n’y auront plus accès.
                Cette action est irréversible.
            </p>
            <form id="delete-account-form" class="mt-4" novalidate @submit.prevent="deleteAccount">
                <FormField
                    id="delete-account-password"
                    v-model="deletionForm.data.password"
                    label="Mot de passe, pour confirmer"
                    type="password"
                    autocomplete="current-password"
                    :error="deletionForm.errors.value.password"
                    required
                />
            </form>
            <p v-if="deletionError" role="alert" class="mt-3 text-red-600">{{ deletionError }}</p>

            <template #actions>
                <button
                    type="button"
                    :disabled="deletionForm.isProcessing.value"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-900 hover:bg-zinc-50 disabled:opacity-60 sm:w-auto sm:py-2"
                    @click="isConfirmingDeletion = false"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    form="delete-account-form"
                    :disabled="deletionForm.isProcessing.value"
                    class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 sm:w-auto sm:py-2"
                >
                    {{ deletionForm.isProcessing.value ? 'Suppression…' : 'Supprimer définitivement' }}
                </button>
            </template>
        </ModalDialog>
    </AppLayout>
</template>
