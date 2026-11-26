<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import ContributionsField from '@/components/ContributionsField.vue';
import FormField from '@/components/FormField.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind, Visibility } from '@/types/models';

export interface NameVisibilityPayload {
    name: string;
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}

/**
 * Settings form of a group or collection (name, visibility, contributions), saving confirmed in a notification.
 * With `cancelable`, a "Annuler" button emits `cancel`. Starts from `initial`, read when the form is mounted.
 */
const props = defineProps<{
    initial: NameVisibilityPayload;
    kind: FavoriteKind;
    /** Label of the name field, e.g. "Nom du groupe". */
    nameLabel: string;
    /** Saves the changes; validation errors (422) are shown in the form. */
    save: (payload: NameVisibilityPayload) => Promise<void>;
    cancelable?: boolean;
}>();

const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

const { showToast } = useToasts();
const { t } = useI18n();

const form = useForm<NameVisibilityPayload>({ ...props.initial });

async function submit(): Promise<void> {
    if (await form.submit((data) => props.save({ ...data }))) {
        showToast(t(`editing.done.${itemKey(props.kind)}`));
        emit('saved');
    }
}
</script>

<template>
    <form class="space-y-4" novalidate @submit.prevent="submit" @keydown.esc="cancelable && emit('cancel')">
        <FormField id="edit-name" v-model="form.data.name" :label="nameLabel" :error="form.errors.value.name" required />
        <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
        <ContributionsField
            v-model="form.data.accepts_contributions"
            v-model:members-only="form.data.contributions_members_only"
            :kind="kind"
            :visibility="form.data.visibility"
            :error="form.errors.value.accepts_contributions"
            :members-only-error="form.errors.value.contributions_members_only"
        />
        <div class="flex justify-end gap-2">
            <button
                v-if="cancelable"
                type="button"
                class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800"
                @click="emit('cancel')"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                type="submit"
                :disabled="form.isProcessing.value"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            >
                {{ $t('common.save') }}
            </button>
        </div>
    </form>
</template>
