<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { duplicateGroupCollection } from '@/api/groupCollections';
import { duplicateWordGroup } from '@/api/wordGroups';
import ModalDialog from '@/components/ModalDialog.vue';
import { useToasts } from '@/composables/useToasts';
import { itemKey } from '@/lib/i18n';
import type { ValidationErrorResponse } from '@/types/api';
import type { FavoriteKind } from '@/types/models';

/**
 * "Dupliquer" button of a group or collection page, confirmed in a modal explaining what is copied (on a collection,
 * with a "Copier aussi les groupes" checkbox, checked by default, to link the original groups instead); opens the copy
 * with a status message.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const router = useRouter();
const { showToast } = useToasts();
const { t } = useI18n();
const isConfirming = ref(false);
const isDuplicating = ref(false);
const error = ref<string | null>(null);
const copyWordGroups = ref(true);

const title = computed(() => t(`duplication.title.${itemKey(props.kind)}`));
const description = computed(() => t(`duplication.description.${itemKey(props.kind)}`));

function askConfirmation(): void {
    error.value = null;
    copyWordGroups.value = true;
    isConfirming.value = true;
}

/** Copies the item into an item of the current user, opens the copy and confirms it in a notification. */
async function duplicate(): Promise<void> {
    isDuplicating.value = true;
    error.value = null;
    try {
        if (props.kind === 'word-groups') {
            const copy = await duplicateWordGroup(props.id);
            isConfirming.value = false;
            await router.push({ name: 'word-groups.show', params: { id: copy.id } });
            showToast(t('duplication.done.wordGroup'));
        } else {
            const copiesWordGroups = copyWordGroups.value;
            const copy = await duplicateGroupCollection(props.id, copiesWordGroups);
            isConfirming.value = false;
            await router.push({ name: 'group-collections.show', params: { id: copy.id } });
            showToast(
                t(
                    copiesWordGroups ? 'duplication.done.groupCollection' : 'duplication.done.groupCollectionLinked',
                    copy.hidden_word_groups_count,
                ),
            );
        }
    } catch (caughtError) {
        error.value =
            isAxiosError<ValidationErrorResponse>(caughtError) && caughtError.response?.status === 422
                ? caughtError.response.data.message
                : t('duplication.failed');
    } finally {
        isDuplicating.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800"
        aria-haspopup="dialog"
        @click="askConfirmation"
    >
        {{ $t('duplication.button') }}
    </button>

    <ModalDialog v-model:open="isConfirming" :title="title" :is-dismissible="!isDuplicating">
        <p>{{ description }}</p>
        <label
            v-if="kind === 'group-collections'"
            class="mt-3 flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3 has-checked:border-brand-500 dark:has-checked:border-brand-400 has-checked:bg-brand-50 dark:has-checked:bg-brand-950"
        >
            <input v-model="copyWordGroups" type="checkbox" class="mt-1" :disabled="isDuplicating" />
            <span>
                <span class="block text-sm font-medium">{{ $t('duplication.copyWordGroups.label') }}</span>
                <span class="block text-sm text-zinc-500 dark:text-zinc-400">
                    {{
                        copyWordGroups
                            ? $t('duplication.copyWordGroups.description.copied')
                            : $t('duplication.copyWordGroups.description.linked')
                    }}
                </span>
            </span>
        </label>
        <p v-if="error" role="alert" class="mt-3 text-red-600 dark:text-red-400">{{ error }}</p>

        <template #actions>
            <button
                type="button"
                :disabled="isDuplicating"
                class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="isConfirming = false"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                type="button"
                autofocus
                :disabled="isDuplicating"
                class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60 sm:w-auto sm:py-2"
                @click="duplicate"
            >
                {{ isDuplicating ? $t('duplication.processing') : $t('duplication.button') }}
            </button>
        </template>
    </ModalDialog>
</template>
