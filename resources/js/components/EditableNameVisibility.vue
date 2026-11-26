<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ActionsMenu from '@/components/ActionsMenu.vue';
import ContributionsBadge from '@/components/ContributionsBadge.vue';
import ContributionsField from '@/components/ContributionsField.vue';
import FormField from '@/components/FormField.vue';
import LanguageBadge from '@/components/LanguageBadge.vue';
import UserLink from '@/components/UserLink.vue';
import VisibilityBadge from '@/components/VisibilityBadge.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import { useForm } from '@/composables/useForm';
import { useToasts } from '@/composables/useToasts';
import { actionButtonClass } from '@/lib/actionsMenu';
import { formatDateTime } from '@/lib/format';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind, Locale, User, Visibility } from '@/types/models';

export interface NameVisibilityPayload {
    name: string;
    visibility: Visibility;
    accepts_contributions: boolean;
    contributions_members_only: boolean;
}

/**
 * Page title built like the cards: a tinted header with the name (on two lines at most) and, in its corner, the
 * `after-title` slot (the favorite star) then "⋮" opening the actions (`ActionsMenu`: "Modifier", which turns the title
 * into an inline form, saving confirmed in a notification, then the `actions` slot); under it, the language and
 * visibility badges and whether contributions are open, then the creator and the last update date. The `main-action`
 * slot (the export) follows the card. Used by the group and collection pages.
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

const { showToast } = useToasts();
const { t } = useI18n();

const item = computed(() => itemKey(props.kind));
const isMembersOnly = computed(() => props.contributionsMembersOnly && props.visibility === 'public');

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
        showToast(t(`editing.done.${itemKey(props.kind)}`));
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

    <div v-else>
        <!-- Like the cards: a tinted header with the name, then the details. -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
            <header class="flex items-start gap-2 rounded-t-xl border-b border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800/70 px-4 py-3">
                <h1 class="line-clamp-2 min-w-0 flex-1 text-2xl font-semibold break-words" :title="name">{{ name }}</h1>
                <div class="flex shrink-0 items-center gap-1 pt-0.5">
                    <slot name="after-title" />
                    <ActionsMenu>
                        <button v-if="canEdit" type="button" :class="actionButtonClass" @click="startEditing">
                            {{ $t('editing.button') }}
                        </button>
                        <slot name="actions" />
                    </ActionsMenu>
                </div>
            </header>
            <div class="space-y-2 px-4 py-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <LanguageBadge :language="language" />
                    <VisibilityBadge :visibility="visibility" />
                    <ContributionsBadge
                        v-if="acceptsContributions"
                        :members-only="isMembersOnly"
                    />
                </div>
                <div class="space-y-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                    <p v-if="creator">
                        <i18n-t :keypath="`editing.createdBy.${item}`" scope="global">
                            <template #user><UserLink :user="creator" /></template>
                        </i18n-t>
                    </p>
                    <p v-if="updatedAt" class="text-zinc-500 dark:text-zinc-400">
                        <i18n-t keypath="editing.updatedOn" scope="global">
                            <template #date><time :datetime="updatedAt">{{ formatDateTime(updatedAt) }}</time></template>
                        </i18n-t>
                    </p>
                </div>
            </div>
        </div>
        <div v-if="$slots['main-action']" class="mt-4">
            <slot name="main-action" />
        </div>
    </div>
</template>
