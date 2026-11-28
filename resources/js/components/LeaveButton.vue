<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { leaveItem } from '@/api/sharing';
import AppIcon from '@/components/AppIcon.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useToasts } from '@/composables/useToasts';
import { dangerButtonClass, labelHiddenOnPhonesClass } from '@/lib/buttons';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind, Visibility } from '@/types/models';

/**
 * "Quitter" button of a group or collection page, for its members, confirmed in a modal (`ConfirmDialog`). Leaving a
 * private item goes back to the dashboard; leaving a public one (whose contributions are restricted to its members)
 * keeps the page, which reloads it on `left`. Both are confirmed in a notification.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    visibility: Visibility;
}>();

const emit = defineEmits<{
    left: [];
}>();

const router = useRouter();
const { showToast } = useToasts();
const { t } = useI18n();
const isConfirming = ref(false);
const isLeaving = ref(false);
const error = ref<string | null>(null);

const item = computed(() => itemKey(props.kind));
const title = computed(() => t(`leaving.title.${item.value}`));
const message = computed(() => t(`leaving.message.${props.visibility}.${item.value}`));

function askConfirmation(): void {
    error.value = null;
    isConfirming.value = true;
}

async function confirmLeave(): Promise<void> {
    isLeaving.value = true;
    error.value = null;
    try {
        await leaveItem(props.kind, props.id);
        isConfirming.value = false;
        if (props.visibility === 'private') {
            await router.push({ name: 'dashboard' });
        } else {
            emit('left');
        }
        showToast(t(`leaving.done.${item.value}`));
    } catch {
        error.value = t('leaving.failed');
    } finally {
        isLeaving.value = false;
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
        <AppIcon name="leave" />
        <span :class="labelHiddenOnPhonesClass">{{ $t('leaving.button') }}</span>
    </button>

    <ConfirmDialog
        v-model:open="isConfirming"
        :title="title"
        :message="message"
        :confirm-label="$t('leaving.button')"
        :processing-label="$t('leaving.processing')"
        :is-processing="isLeaving"
        :error="error"
        @confirm="confirmLeave"
    />
</template>
