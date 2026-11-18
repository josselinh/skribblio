<script setup lang="ts">
import { ref } from 'vue';
import FormField from '@/components/FormField.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import type { Visibility } from '@/types/models';

export interface NameVisibilityPayload {
    name: string;
    visibility: Visibility;
}

/**
 * Page title showing a name and a visibility badge, with a "Modifier" button that turns it
 * into an inline form. Used by the group and collection pages.
 */
const props = defineProps<{
    name: string;
    visibility: Visibility;
    canEdit: boolean;
    /** Label of the name field, e.g. "Nom du groupe". */
    nameLabel: string;
    /** Saves the changes; validation errors (422) are shown in the form. */
    save: (payload: NameVisibilityPayload) => Promise<void>;
}>();

const isEditing = ref(false);
const form = useForm<NameVisibilityPayload>({ name: props.name, visibility: props.visibility });

function startEditing(): void {
    form.data.name = props.name;
    form.data.visibility = props.visibility;
    form.errors.value = {};
    isEditing.value = true;
}

async function submit(): Promise<void> {
    if (await form.submit((data) => props.save({ ...data }))) {
        isEditing.value = false;
    }
}
</script>

<template>
    <form
        v-if="isEditing"
        class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm"
        novalidate
        @submit.prevent="submit"
        @keydown.esc="isEditing = false"
    >
        <FormField id="edit-name" v-model="form.data.name" :label="nameLabel" :error="form.errors.value.name" required />
        <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
        <div class="flex justify-end gap-2">
            <button
                type="button"
                class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium hover:bg-zinc-50"
                @click="isEditing = false"
            >
                Annuler
            </button>
            <button
                type="submit"
                :disabled="form.isProcessing.value"
                class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-60"
            >
                Enregistrer
            </button>
        </div>
    </form>

    <div v-else class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <h1 class="truncate text-2xl font-semibold">{{ name }}</h1>
            <VisibilityBadge :visibility="visibility" />
            <slot name="after-title" />
        </div>
        <button
            v-if="canEdit"
            type="button"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium hover:bg-zinc-50"
            @click="startEditing"
        >
            Modifier
        </button>
    </div>
</template>
