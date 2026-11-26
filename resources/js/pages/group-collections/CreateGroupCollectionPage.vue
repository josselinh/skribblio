<script setup lang="ts">
import { useRouter } from 'vue-router';
import { createGroupCollection } from '@/api/groupCollections';
import AppLayout from '@/components/AppLayout.vue';
import ContributionsField from '@/components/ContributionsField.vue';
import FormField from '@/components/FormField.vue';
import LanguageField from '@/components/LanguageField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import { preferredContentLanguage } from '@/lib/contentLanguage';
import type { Visibility } from '@/types/models';

/** Creates an empty collection; its groups are added afterwards, on its page. */
const router = useRouter();

const form = useForm({
    name: '',
    language: preferredContentLanguage(),
    visibility: 'private' as Visibility,
    accepts_contributions: false,
    contributions_members_only: false,
});

async function submit(): Promise<void> {
    await form.submit(async (data) => {
        const groupCollection = await createGroupCollection(data);
        // A new collection is empty: its edit page lets the user add groups right away.
        await router.push({ name: 'group-collections.edit', params: { id: groupCollection.id } });
    });
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <h1 class="text-2xl font-semibold">{{ $t('groupCollections.create.title') }}</h1>

            <form
                class="mt-6 space-y-6 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-sm"
                novalidate
                @submit.prevent="submit"
            >
                <FormField
                    id="name"
                    v-model="form.data.name"
                    :label="$t('groupCollections.fields.name')"
                    :error="form.errors.value.name"
                    required
                />
                <LanguageField v-model="form.data.language" kind="group-collections" :error="form.errors.value.language" />
                <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
                <ContributionsField
                    is-creation
                    v-model="form.data.accepts_contributions"
                    v-model:members-only="form.data.contributions_members_only"
                    :visibility="form.data.visibility"
                    kind="group-collections"
                    :error="form.errors.value.accepts_contributions"
                    :members-only-error="form.errors.value.contributions_members_only"
                />
                <PrimaryButton :is-loading="form.isProcessing.value">{{ $t('groupCollections.create.submit') }}</PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
