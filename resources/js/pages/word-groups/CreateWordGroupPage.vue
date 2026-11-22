<script setup lang="ts">
import { useRouter } from 'vue-router';
import { createWordGroup } from '@/api/wordGroups';
import AppLayout from '@/components/AppLayout.vue';
import ContributionsField from '@/components/ContributionsField.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import type { Visibility } from '@/types/models';

const router = useRouter();

const form = useForm({ name: '', visibility: 'private' as Visibility, accepts_contributions: false, contributions_members_only: false });

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
            <h1 class="text-2xl font-semibold">{{ $t('wordGroups.create.title') }}</h1>

            <form
                class="mt-6 space-y-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm"
                novalidate
                @submit.prevent="submit"
            >
                <FormField id="name" v-model="form.data.name" :label="$t('wordGroups.fields.name')" :error="form.errors.value.name" required />
                <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
                <ContributionsField
                    v-model="form.data.accepts_contributions"
                    v-model:members-only="form.data.contributions_members_only"
                    :visibility="form.data.visibility"
                    kind="word-groups"
                    :error="form.errors.value.accepts_contributions"
                    :members-only-error="form.errors.value.contributions_members_only"
                />
                <PrimaryButton :is-loading="form.isProcessing.value">{{ $t('wordGroups.create.submit') }}</PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
