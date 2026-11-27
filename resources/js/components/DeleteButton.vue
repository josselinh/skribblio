<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { deleteGroupCollection } from '@/api/groupCollections';
import { deleteWordGroup } from '@/api/wordGroups';
import AppIcon from '@/components/AppIcon.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useToasts } from '@/composables/useToasts';
import { dangerButtonClass } from '@/lib/buttons';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind } from '@/types/models';

/**
 * "Supprimer" button of a group or collection page, confirmed in a modal (`ConfirmDialog`); once deleted, goes back to the
 * dashboard and confirms it in a notification.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
}>();

const router = useRouter();
const { showToast } = useToasts();
const { t } = useI18n();
const isConfirming = ref(false);
const isDeleting = ref(false);
const error = ref<string | null>(null);

const item = itemKey(props.kind);
const title = computed(() => t(`deletion.title.${item}`));
const warning = computed(() => t(`deletion.warning.${item}`));

function askConfirmation(): void {
    error.value = null;
    isConfirming.value = true;
}

async function confirmDelete(): Promise<void> {
    isDeleting.value = true;
    error.value = null;
    try {
        if (props.kind === 'word-groups') {
            await deleteWordGroup(props.id);
        } else {
            await deleteGroupCollection(props.id);
        }
        isConfirming.value = false;
        await router.push({ name: 'dashboard' });
        showToast(t(`deletion.done.${item}`));
    } catch {
        error.value = t('deletion.failed');
    } finally {
        isDeleting.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        :class="dangerButtonClass"
        aria-haspopup="dialog"
        @click="askConfirmation"
    >
        <AppIcon name="delete" />
        {{ $t('deletion.button') }}
    </button>

    <ConfirmDialog
        v-model:open="isConfirming"
        :title="title"
        :message="warning"
        :confirm-label="$t('deletion.confirm')"
        :processing-label="$t('deletion.processing')"
        :is-processing="isDeleting"
        :error="error"
        @confirm="confirmDelete"
    />
</template>
