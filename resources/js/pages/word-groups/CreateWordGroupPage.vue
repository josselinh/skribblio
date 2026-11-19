<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router';
import { createWordGroup } from '@/api/wordGroups';
import AppLayout from '@/components/AppLayout.vue';
import ContributionsField from '@/components/ContributionsField.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import type { Visibility } from '@/types/models';

const router = useRouter();

const form = useForm({ name: '', visibility: 'private' as Visibility, accepts_contributions: true });

async function submit(): Promise<void> {
    await form.submit(async (data) => {
        const wordGroup = await createWordGroup(data);
        await router.push({ name: 'word-groups.show', params: { id: wordGroup.id } });
    });
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
                ← Retour à mes groupes
            </RouterLink>
            <h1 class="mt-2 text-2xl font-semibold">Nouveau groupe</h1>

            <form
                class="mt-6 space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm"
                novalidate
                @submit.prevent="submit"
            >
                <FormField id="name" v-model="form.data.name" label="Nom du groupe" :error="form.errors.value.name" required />
                <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
                <ContributionsField
                    v-model="form.data.accepts_contributions"
                    kind="word-groups"
                    :error="form.errors.value.accepts_contributions"
                />
                <PrimaryButton :is-loading="form.isProcessing.value">Créer le groupe</PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
