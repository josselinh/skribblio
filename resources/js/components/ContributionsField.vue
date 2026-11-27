<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ModalDialog from '@/components/ModalDialog.vue';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind, Visibility } from '@/types/models';

/**
 * "Open to contributions" checkbox of the group and collection forms. On a public item, it also lets the
 * creator open the contributions to everyone or only to the members they accept through the share link. When
 * only invited people can contribute (private item, or members only), it says where the invitation link is:
 * the "Partage" section of the edit page (`isCreation`: once the item is created). The checkbox carries a one-line
 * description; "En savoir plus" opens the detailed rules (contributors' rights, closing, private and closed) in a
 * modal.
 */
const props = defineProps<{
    kind: FavoriteKind;
    visibility: Visibility;
    isCreation?: boolean;
    error?: string;
    membersOnlyError?: string;
}>();

const model = defineModel<boolean>({ required: true });
const membersOnly = defineModel<boolean>('membersOnly', { required: true });

const { t } = useI18n();

const isGroup = computed(() => props.kind === 'word-groups');

/** Contributors must be invited through the share link. */
const needsInvitation = computed(() => model.value && (props.visibility === 'private' || membersOnly.value));

const inviteHint = computed(() =>
    props.isCreation ? t(`contributions.field.inviteHint.creation.${itemKey(props.kind)}`) : t('contributions.field.inviteHint.edition'),
);

const description = computed(() => t(`contributions.field.description.${itemKey(props.kind)}`));

const isHelpOpen = ref(false);

const helpPoints = computed(() =>
    (['contributors', 'closed', 'privateClosed'] as const).map((point) => t(`contributions.field.help.${point}.${itemKey(props.kind)}`)),
);

const choices = computed(() => [
    {
        value: false,
        label: isGroup.value ? t('contributions.field.everyone.wordGroup') : t('contributions.field.everyone.groupCollection'),
        description: t('contributions.field.everyone.description'),
    },
    {
        value: true,
        label: t('contributions.field.membersOnly.label'),
        description: t('contributions.field.membersOnly.description'),
    },
]);
</script>

<template>
    <div>
        <label
            class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
        >
            <input v-model="model" type="checkbox" class="mt-1" />
            <span>
                <span class="block text-sm font-medium">{{ $t('contributions.field.label') }}</span>
                <span class="block text-sm text-zinc-500 dark:text-zinc-400">
                    {{ description }}
                    <!-- A button inside the label does not toggle the checkbox. -->
                    <button
                        type="button"
                        class="relative font-medium text-brand-700 dark:text-brand-300 underline hover:text-brand-800 dark:hover:text-brand-200"
                        @click.prevent="isHelpOpen = true"
                    >
                        {{ $t('contributions.field.learnMore') }}
                    </button>
                </span>
            </span>
        </label>
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

        <fieldset v-if="model && visibility === 'public'" class="mt-2 space-y-2 pl-6">
            <legend class="mb-2 text-sm font-medium">{{ $t('contributions.field.who') }}</legend>
            <label
                v-for="choice in choices"
                :key="String(choice.value)"
                class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
            >
                <input v-model="membersOnly" type="radio" :value="choice.value" class="mt-1" />
                <span>
                    <span class="block text-sm font-medium">{{ choice.label }}</span>
                    <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ choice.description }}</span>
                </span>
            </label>
            <p v-if="membersOnlyError" class="text-sm text-red-600 dark:text-red-400">{{ membersOnlyError }}</p>
        </fieldset>

        <p v-if="needsInvitation" class="mt-2 pl-6 text-sm text-zinc-500 dark:text-zinc-400">{{ inviteHint }}</p>

        <ModalDialog v-model:open="isHelpOpen" :title="$t('contributions.field.label')">
            <ul class="list-disc space-y-2 pl-5">
                <li v-for="point in helpPoints" :key="point">{{ point }}</li>
            </ul>
            <template #actions>
                <button
                    type="button"
                    autofocus
                    class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 sm:w-auto sm:py-2"
                    @click="isHelpOpen = false"
                >
                    {{ $t('common.close') }}
                </button>
            </template>
        </ModalDialog>
    </div>
</template>
