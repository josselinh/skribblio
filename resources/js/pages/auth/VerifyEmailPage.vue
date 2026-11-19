<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthCard from '@/components/AuthCard.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const status = ref<string | null>(null);
const isSending = ref(false);

async function resend(): Promise<void> {
    isSending.value = true;
    try {
        await auth.resendVerificationEmail();
        status.value = 'Un nouveau lien de confirmation vient de vous être envoyé.';
    } finally {
        isSending.value = false;
    }
}

async function logout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <AuthCard
        title="Confirmez votre adresse email"
        :description="`Nous avons envoyé un lien de confirmation à ${auth.user?.email}. Cliquez dessus pour activer votre compte.`"
    >
        <StatusMessage :message="status" />
        <PrimaryButton type="button" :is-loading="isSending" @click="resend">Renvoyer le lien</PrimaryButton>

        <template #footer>
            <button type="button" class="underline hover:text-zinc-900 dark:hover:text-zinc-100" @click="logout">Se déconnecter</button>
        </template>
    </AuthCard>
</template>
