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
    <AuthCard title="Créer un compte" description="Un email de confirmation vous sera envoyé.">
        <form class="space-y-4" novalidate @submit.prevent="submit">
            <FormField
                id="name"
                v-model="form.data.name"
                label="Nom"
                autocomplete="name"
                :error="form.errors.value.name"
                required
            />
            <FormField
                id="email"
                v-model="form.data.email"
                label="Adresse email"
                type="email"
                autocomplete="email"
                :error="form.errors.value.email"
                required
            />
            <FormField
                id="password"
                v-model="form.data.password"
                label="Mot de passe"
                type="password"
                autocomplete="new-password"
                :error="form.errors.value.password"
                required
            />
            <FormField
                id="password_confirmation"
                v-model="form.data.password_confirmation"
                label="Confirmation du mot de passe"
                type="password"
                autocomplete="new-password"
                :error="form.errors.value.password_confirmation"
                required
            />
            <PrimaryButton :is-loading="form.isProcessing.value">Créer mon compte</PrimaryButton>
        </form>

        <template #footer>
            Déjà inscrit ?
            <RouterLink :to="{ name: 'login' }" class="font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200">Se connecter</RouterLink>
        </template>
    </AuthCard>
</template>
