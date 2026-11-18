<script setup lang="ts">
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AuthCard from '@/components/AuthCard.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import { useForm } from '@/composables/useForm';
import { consumePendingRedirect } from '@/lib/pendingRedirect';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = useForm({ email: '', password: '', remember: false });

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
    <AuthCard title="Connexion" description="Connectez-vous pour accéder à vos listes de mots.">
        <form class="space-y-4" novalidate @submit.prevent="submit">
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
                autocomplete="current-password"
                :error="form.errors.value.password"
                required
            />
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2">
                    <input v-model="form.data.remember" type="checkbox" class="rounded border-zinc-300" />
                    Se souvenir de moi
                </label>
                <RouterLink :to="{ name: 'forgot-password' }" class="text-zinc-600 underline hover:text-zinc-900">
                    Mot de passe oublié ?
                </RouterLink>
            </div>
            <PrimaryButton :is-loading="form.isProcessing.value">Se connecter</PrimaryButton>
        </form>

        <template #footer>
            Pas encore de compte ?
            <RouterLink :to="{ name: 'register' }" class="font-medium text-zinc-900 underline">Créer un compte</RouterLink>
        </template>
    </AuthCard>
</template>
