<script setup lang="ts">
import { ref } from 'vue';
import ContributionsBadge from '@/components/ContributionsBadge.vue';
import ContributionsField from '@/components/ContributionsField.vue';
import FormField from '@/components/FormField.vue';
import UserLink from '@/components/UserLink.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import type { FavoriteKind, User, Visibility } from '@/types/models';

export interface NameVisibilityPayload {
    name: string;
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}

/**
 * Page title showing a name, a visibility badge, whether contributions are open and the creator, with a "Modifier"
 * button that turns it into an inline form (saving is confirmed in a notification), followed by the `actions` slot (e.g. deletion). Used by the group and
 * collection pages.
 */
const props = defineProps<{
    name: string;
    visibility: Visibility;
    acceptsContributions: boolean;
    contributionsMembersOnly: boolean;
    kind: FavoriteKind;
    /** Shown as "par X" under the title, "vous" for the current user. */
    creator?: Pick<User, 'id' | 'name'>;
    canEdit: boolean;
    /** Label of the name field, e.g. "Nom du groupe". */
    nameLabel: string;
    /** Saves the changes; validation errors (422) are shown in the form. */
    save: (payload: NameVisibilityPayload) => Promise<void>;
}>();

const { showToast } = useToasts();

const isEditing = ref(false);
const form = useForm<NameVisibilityPayload>({
    name: props.name,
    visibility: props.visibility,
    accepts_contributions: props.acceptsContributions,
    contributions_members_only: props.contributionsMembersOnly,
});

function startEditing(): void {
    form.data.name = props.name;
    form.data.visibility = props.visibility;
    form.data.accepts_contributions = props.acceptsContributions;
    form.data.contributions_members_only = props.contributionsMembersOnly;
    form.errors.value = {};
    isEditing.value = true;
}

async function submit(): Promise<void> {
    if (await form.submit((data) => props.save({ ...data }))) {
        isEditing.value = false;
        showToast(props.kind === 'word-groups' ? 'Groupe modifié.' : 'Collection modifiée.');
    }
}
</script>

<template>
    <form
        v-if="isEditing"
        class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm"
        novalidate
        @submit.prevent="submit"
        @keydown.esc="isEditing = false"
    >
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
                type="button"
                class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800"
                @click="isEditing = false"
            >
                Annuler
            </button>
            <button
                type="submit"
                :disabled="form.isProcessing.value"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            >
                Enregistrer
            </button>
        </div>
    </form>

    <div v-else class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <h1 class="truncate text-2xl font-semibold">{{ name }}</h1>
                <VisibilityBadge :visibility="visibility" />
                <ContributionsBadge
                    v-if="acceptsContributions"
                    :members-only="contributionsMembersOnly && visibility === 'public'"
                />
                <slot name="after-title" />
            </div>
            <p v-if="creator" class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                par <UserLink :user="creator" self-label="vous" />
            </p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <button
                v-if="canEdit"
                type="button"
                class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800"
                @click="startEditing"
            >
                Modifier
            </button>
            <slot name="actions" />
        </div>
    </div>
</template>
