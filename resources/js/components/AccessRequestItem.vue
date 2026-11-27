<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { decideAccessRequest } from '@/api/sharing';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RelativeTime from '@/components/RelativeTime.vue';
import ShareTypeField from '@/components/ShareTypeField.vue';
import UserLink from '@/components/UserLink.vue';
import { useToasts } from '@/composables/useToasts';
import { itemKey } from '@/lib/i18n';
import type { ValidationErrorResponse } from '@/types/api';
import type { AccessRequest, ShareType } from '@/types/models';

/**
 * One pending access (or contribution) request with its Accept and Refuse buttons; accepting asks for the type of
 * sharing (partial by default) and the decision is confirmed in a notification.
 */
const props = withDefaults(
    defineProps<{
        accessRequest: AccessRequest;
        /** Shows which group or collection is requested (dashboard), not needed on the item's page. */
        showItem?: boolean;
    }>(),
    { showItem: false },
);

const emit = defineEmits<{
    decided: [accessRequest: AccessRequest, accepted: boolean];
}>();

const { showToast } = useToasts();
const { t } = useI18n();

/** "Bob demande l'accès" or, with `showItem`, "Bob demande l'accès au groupe Animaux". */
const requestKeypath = computed(
    () => `sharing.request.${props.accessRequest.purpose}.${props.showItem ? itemKey(props.accessRequest.kind) : 'withoutItem'}`,
);

const isDeciding = ref(false);
const hasFailed = ref(false);
/** Reason given by the server for a refused decision (e.g. the item is closed to contributions). */
const failureMessage = ref<string | null>(null);
const isConfirmingAcceptance = ref(false);
const shareType = ref<ShareType>('partial');

function openAcceptance(): void {
    shareType.value = 'partial';
    hasFailed.value = false;
    isConfirmingAcceptance.value = true;
}

const acceptanceMessage = computed(() => {
    const { requester, item, kind, purpose } = props.accessRequest;

    return t(`sharing.accept.message.${purpose}.${itemKey(kind)}`, { name: requester.name, item: item.name });
});

async function decide(accept: boolean): Promise<void> {
    isDeciding.value = true;
    hasFailed.value = false;
    failureMessage.value = null;
    try {
        await decideAccessRequest(props.accessRequest, accept, accept ? shareType.value : undefined);
        isConfirmingAcceptance.value = false;
        emit('decided', props.accessRequest, accept);
        showToast(t(accept ? 'sharing.request.accepted' : 'sharing.request.refused', { name: props.accessRequest.requester.name }));
    } catch (error) {
        hasFailed.value = true;
        failureMessage.value = isAxiosError<ValidationErrorResponse>(error) && error.response?.status === 422
            ? (error.response.data.errors.status?.[0] ?? null)
            : null;
    } finally {
        isDeciding.value = false;
    }
}
</script>

<template>
    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div class="min-w-0 text-sm">
            <i18n-t :keypath="requestKeypath" tag="p" scope="global">
                <template #user><UserLink :user="accessRequest.requester" class="font-medium" /></template>
                <template #item>
                    <RouterLink
                        :to="{
                            name: accessRequest.kind === 'word-groups' ? 'word-groups.show' : 'group-collections.show',
                            params: { id: accessRequest.item.id },
                        }"
                        class="font-medium underline"
                    >
                        {{ accessRequest.item.name }}
                    </RouterLink>
                </template>
            </i18n-t>
            <p class="text-zinc-500 dark:text-zinc-400">
                <template v-if="accessRequest.via_group_collection">
                    {{ $t('sharing.request.viaCollection', { name: accessRequest.via_group_collection.name }) }} ·
                </template>
                <RelativeTime v-if="accessRequest.created_at" :date="accessRequest.created_at" :capitalized="!accessRequest.via_group_collection" />
            </p>
            <p v-if="hasFailed" role="alert" class="text-red-600 dark:text-red-400">{{ failureMessage ?? $t('common.unexpectedError') }}</p>
        </div>
        <div class="flex gap-2">
            <button
                type="button"
                :disabled="isDeciding"
                class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60"
                @click="decide(false)"
            >
                {{ $t('sharing.request.refuse') }}
            </button>
            <button
                type="button"
                :disabled="isDeciding"
                class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                aria-haspopup="dialog"
                @click="openAcceptance"
            >
                {{ $t('sharing.request.accept') }}
            </button>
        </div>

        <ConfirmDialog
            v-model:open="isConfirmingAcceptance"
            tone="primary"
            :title="$t(`sharing.accept.title.${accessRequest.purpose}`)"
            :message="acceptanceMessage"
            :confirm-label="$t('sharing.request.accept')"
            :processing-label="$t('sharing.accept.processing')"
            :is-processing="isDeciding"
            :error="hasFailed ? (failureMessage ?? $t('common.unexpectedError')) : null"
            @confirm="decide(true)"
        >
            <ShareTypeField v-model="shareType" :kind="accessRequest.kind" />
        </ConfirmDialog>
    </li>
</template>
