<script setup lang="ts">
import { ref } from 'vue';
import { updateWordGroup } from '@/api/wordGroups';
import FormField from '@/components/FormField.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import type { WordGroup } from '@/types/models';

const props = defineProps<{
    wordGroup: WordGroup;
}>();

const emit = defineEmits<{
    updated: [wordGroup: WordGroup];
}>();

const isEditing = ref(false);
const form = useForm({ name: props.wordGroup.name, visibility: props.wordGroup.visibility });

function startEditing(): void {
    form.data.name = props.wordGroup.name;
    form.data.visibility = props.wordGroup.visibility;
    form.errors.value = {};
    isEditing.value = true;
}

async function submit(): Promise<void> {
    const isSuccessful = await form.submit(async (data) => {
        emit('updated', await updateWordGroup(props.wordGroup.id, data));
    });

    if (isSuccessful) {
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
        <FormField id="group-name" v-model="form.data.name" label="Nom du groupe" :error="form.errors.value.name" required />
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
            <h1 class="truncate text-2xl font-semibold">{{ wordGroup.name }}</h1>
            <VisibilityBadge :visibility="wordGroup.visibility" />
        </div>
        <button
            v-if="wordGroup.can.update"
            type="button"
            class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium hover:bg-zinc-50"
            @click="startEditing"
        >
            Modifier
        </button>
    </div>
</template>
