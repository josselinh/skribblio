import { isAxiosError } from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { ensureCsrfCookie, http } from '@/lib/http';
import type { MessageResponse, ResourceResponse } from '@/types/api';
import type { User } from '@/types/models';

export interface LoginPayload {
    email: string;
    password: string;
    remember: boolean;
}

export interface RegisterPayload {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export interface ProfileInformationPayload {
    name: string;
    email: string;
}

export interface UpdatePasswordPayload {
    current_password: string;
    password: string;
    password_confirmation: string;
}

export interface ResetPasswordPayload {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const hasLoaded = ref(false);

    const isAuthenticated = computed(() => user.value !== null);
    const isVerified = computed(() => user.value?.email_verified_at != null);

    async function fetchUser(): Promise<User | null> {
        try {
            const { data } = await http.get<ResourceResponse<User>>('/api/user');
            user.value = data.data;
        } catch (error) {
            if (!isAxiosError(error) || error.response?.status !== 401) {
                throw error;
            }
            user.value = null;
        } finally {
            hasLoaded.value = true;
        }

        return user.value;
    }

    /** Loads the current user once per page load; later calls reuse the result. */
    async function ensureLoaded(): Promise<void> {
        if (!hasLoaded.value) {
            await fetchUser();
        }
    }

    async function login(payload: LoginPayload): Promise<void> {
        await ensureCsrfCookie();
        await http.post('/login', payload);
        await fetchUser();
    }

    async function register(payload: RegisterPayload): Promise<void> {
        await ensureCsrfCookie();
        await http.post('/register', payload);
        await fetchUser();
    }

    async function logout(): Promise<void> {
        await http.post('/logout');
        user.value = null;
    }

    async function resendVerificationEmail(): Promise<void> {
        await http.post('/email/verification-notification');
    }

    async function sendPasswordResetLink(email: string): Promise<string> {
        await ensureCsrfCookie();
        const { data } = await http.post<MessageResponse>('/forgot-password', { email });

        return data.message;
    }

    async function resetPassword(payload: ResetPasswordPayload): Promise<string> {
        await ensureCsrfCookie();
        const { data } = await http.post<MessageResponse>('/reset-password', payload);

        return data.message;
    }

    /**
     * Updates the name and email. Changing the email un-verifies the account until the new address
     * is confirmed (a verification email is sent), which the refreshed user reflects.
     */
    async function updateProfileInformation(payload: ProfileInformationPayload): Promise<void> {
        await http.put('/user/profile-information', payload);
        await fetchUser();
    }

    async function updatePassword(payload: UpdatePasswordPayload): Promise<void> {
        await http.put('/user/password', payload);
    }

    /**
     * Deletes the account (requires the current password) and everything the user created; the server
     * closes the session.
     */
    async function deleteAccount(password: string): Promise<void> {
        await http.delete('/api/user', { data: { password } });
        user.value = null;
    }

    /** Forgets the current user locally, e.g. after the session expired server-side. */
    function clear(): void {
        user.value = null;
    }

    return {
        user,
        isAuthenticated,
        isVerified,
        fetchUser,
        ensureLoaded,
        login,
        register,
        logout,
        resendVerificationEmail,
        sendPasswordResetLink,
        resetPassword,
        updateProfileInformation,
        updatePassword,
        deleteAccount,
        clear,
    };
});
