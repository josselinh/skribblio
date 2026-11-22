<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import AuthCard from '@/components/AuthCard.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const { t } = useI18n();

const status = ref<string | null>(null);
const isSending = ref(false);

async function resend(): Promise<void> {
    isSending.value = true;
    try {
        await auth.resendVerificationEmail();
        status.value = t('auth.verifyEmail.resent');
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
        :title="$t('auth.verifyEmail.title')"
        :description="$t('auth.verifyEmail.description', { email: auth.user?.email ?? '' })"
    >
        <StatusMessage :message="status" />
        <PrimaryButton type="button" :is-loading="isSending" @click="resend">{{ $t('auth.verifyEmail.resend') }}</PrimaryButton>

        <template #footer>
            <button type="button" class="underline hover:text-zinc-900 dark:hover:text-zinc-100" @click="logout">{{ $t('layout.userMenu.logout') }}</button>
        </template>
    </AuthCard>
</template>
