<script setup lang="ts">
import { computed, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { createGroupCollection } from '@/api/groupCollections';
import AppLayout from '@/components/AppLayout.vue';
import ContributionsField from '@/components/ContributionsField.vue';
import FormField from '@/components/FormField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import WordGroupPicker from '@/components/WordGroupPicker.vue';
import { useForm } from '@/composables/useForm';
import type { Visibility, WordGroup } from '@/types/models';

const router = useRouter();

const selectedWordGroups = ref<WordGroup[]>([]);
const form = useForm({ name: '', visibility: 'private' as Visibility, accepts_contributions: false });

/** Errors on the list itself or on one of its items (`word_group_ids.0`…). */
const wordGroupsError = computed(
    () =>
        form.errors.value.word_group_ids ??
        Object.entries(form.errors.value).find(([field]) => field.startsWith('word_group_ids.'))?.[1],
);

async function submit(): Promise<void> {
    await form.submit(async (data) => {
        const groupCollection = await createGroupCollection({
            ...data,
            word_group_ids: selectedWordGroups.value.map((wordGroup) => wordGroup.id),
        });
        await router.push({ name: 'group-collections.show', params: { id: groupCollection.id } });
    });
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-lg">
            <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
                ← Retour au tableau de bord
            </RouterLink>
            <h1 class="mt-2 text-2xl font-semibold">Nouvelle collection</h1>

            <form
                class="mt-6 space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm"
                novalidate
                @submit.prevent="submit"
            >
                <FormField
                    id="name"
                    v-model="form.data.name"
                    label="Nom de la collection"
                    :error="form.errors.value.name"
                    required
                />
                <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
                <ContributionsField
                    v-model="form.data.accepts_contributions"
                    kind="group-collections"
                    :error="form.errors.value.accepts_contributions"
                />
                <WordGroupPicker v-model="selectedWordGroups" :error="wordGroupsError" />
                <PrimaryButton :is-loading="form.isProcessing.value">Créer la collection</PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
