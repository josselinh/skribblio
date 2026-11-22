<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { decideAccessRequest } from '@/api/sharing';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import UserLink from '@/components/UserLink.vue';
import { useToasts } from '@/composables/useToasts';
import { formatDateTime } from '@/lib/format';
import { itemKey } from '@/lib/i18n';
import type { AccessRequest } from '@/types/models';

/** One pending access (or contribution) request with its Accept and Refuse buttons; the decision is confirmed in a notification. */
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
const isConfirmingAcceptance = ref(false);

const acceptanceMessage = computed(() => {
    const { requester, item, kind, purpose } = props.accessRequest;

    return t(`sharing.accept.message.${purpose}.${itemKey(kind)}`, { name: requester.name, item: item.name });
});

async function decide(accept: boolean): Promise<void> {
    isDeciding.value = true;
    hasFailed.value = false;
    try {
        await decideAccessRequest(props.accessRequest, accept);
        isConfirmingAcceptance.value = false;
        emit('decided', props.accessRequest, accept);
        showToast(t(accept ? 'sharing.request.accepted' : 'sharing.request.refused', { name: props.accessRequest.requester.name }));
    } catch {
        hasFailed.value = true;
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
                <time v-if="accessRequest.created_at" :datetime="accessRequest.created_at">
                    {{ formatDateTime(accessRequest.created_at) }}
                </time>
            </p>
            <p v-if="hasFailed" role="alert" class="text-red-600 dark:text-red-400">{{ $t('common.unexpectedError') }}</p>
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
                @click="isConfirmingAcceptance = true"
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
            :error="hasFailed ? $t('common.unexpectedError') : null"
            @confirm="decide(true)"
        />
    </li>
</template>
