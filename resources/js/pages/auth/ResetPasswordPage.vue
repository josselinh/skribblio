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
    <AuthCard title="Nouveau mot de passe">
        <template v-if="status">
            <StatusMessage :message="status" />
            <RouterLink :to="{ name: 'login' }" class="text-sm font-medium text-zinc-900 underline">Se connecter</RouterLink>
        </template>

        <form v-else class="space-y-4" novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.data.email"
                label="Adresse email"
                type="email"
                autocomplete="email"
                :error="form.errors.value.email ?? form.errors.value.token"
                required
            />
            <FormField
                id="password"
                v-model="form.data.password"
                label="Nouveau mot de passe"
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
            <PrimaryButton :is-loading="form.isProcessing.value">Enregistrer</PrimaryButton>
        </form>
    </AuthCard>
</template>
