<script setup lang="ts">
import { ref } from 'vue';
import ActionsMenu from '@/components/ActionsMenu.vue';
import ItemHeader from '@/components/ItemHeader.vue';
import NameVisibilityForm, { type NameVisibilityPayload } from '@/components/NameVisibilityForm.vue';
import { actionButtonClass } from '@/lib/actionsMenu';
import type { FavoriteKind, Locale, User, Visibility } from '@/types/models';

/**
 * Page title of a collection, editable in place: `ItemHeader` with, in its corner, the `after-title` slot (the favorite
 * star) then "⋮" opening the actions (`ActionsMenu`: "Modifier", which turns the title into `NameVisibilityForm`, then
 * the `actions` slot). The `main-action` slot (the export) follows the card.
 */
const props = defineProps<{
    name: string;
    /** Chosen on creation, it cannot be changed: only shown as a badge. */
    language: Locale;
    visibility: Visibility;
    acceptsContributions: boolean;
    contributionsMembersOnly: boolean;
    kind: FavoriteKind;
    /** Shown as "Créé par X" under the badges (left out for visitors). */
    creator?: Pick<User, 'id' | 'name'>;
    /** Shown as "Dernière mise à jour le …" on the next line. */
    updatedAt: string | null;
    canEdit: boolean;
    /** Label of the name field, e.g. "Nom du groupe". */
    nameLabel: string;
    /** Saves the changes; validation errors (422) are shown in the form. */
    save: (payload: NameVisibilityPayload) => Promise<void>;
}>();

const isEditing = ref(false);
</script>

<template>
    <div
        v-if="isEditing"
        class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm"
    >
        <NameVisibilityForm
            :initial="{
                name: props.name,
                visibility: props.visibility,
                accepts_contributions: props.acceptsContributions,
                contributions_members_only: props.contributionsMembersOnly,
            }"
            :kind="kind"
            :name-label="nameLabel"
            :save="save"
            cancelable
            @saved="isEditing = false"
            @cancel="isEditing = false"
        />
    </div>

    <div v-else>
        <ItemHeader
            :name="name"
            :language="language"
            :visibility="visibility"
            :accepts-contributions="acceptsContributions"
            :contributions-members-only="contributionsMembersOnly"
            :kind="kind"
            :creator="creator"
            :updated-at="updatedAt"
        >
            <template #after-title>
                <slot name="after-title" />
                <ActionsMenu>
                    <button v-if="canEdit" type="button" :class="actionButtonClass" @click="isEditing = true">
                        {{ $t('editing.button') }}
                    </button>
                    <slot name="actions" />
                </ActionsMenu>
            </template>
        </ItemHeader>
        <div v-if="$slots['main-action']" class="mt-4">
            <slot name="main-action" />
        </div>
    </div>
</template>
