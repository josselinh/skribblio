<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { fetchItemAccess, revokeAccess, updateMemberShareType } from '@/api/sharing';
import AccessRequestItem from '@/components/AccessRequestItem.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { useToasts } from '@/composables/useToasts';
import { formatDateTime } from '@/lib/format';
import type { InactiveSharingReason } from '@/lib/contributions';
import { itemKey } from '@/lib/i18n';
import type { AccessPurpose, FavoriteKind, ItemAccess, ItemMember, ShareType } from '@/types/models';

/**
 * Pending requests and members of a shared item, for its creator: people with access to a private item,
 * or allowed to contribute to a public item open to its members only (`purpose`). Changing the type of sharing of a
 * member is confirmed in `ConfirmDialog`, the selector keeping the current type until then. It stays available
 * while the item cannot be shared (`inactiveReason`): the help then says what the members can still do and that
 * their type will apply once it is shareable again. Emits the number of members
 * once loaded, so that the page highlights "Inviter" while there is none.
 */
const props = defineProps<{
    kind: FavoriteKind;
    id: string;
    purpose: AccessPurpose;
    /** Why the sharing has no effect for now (see `inactiveSharingReason()`), null while the item is shareable. */
    inactiveReason: InactiveSharingReason | null;
}>();

const emit = defineEmits<{
    membersCount: [count: number];
}>();

const access = ref<ItemAccess | null>(null);
const hasFailed = ref(false);
type Member = ItemMember;
const shareTypes: ShareType[] = ['partial', 'full'];

/** Member whose access is being confirmed for removal, in `ConfirmDialog`. */
const memberBeingRevoked = ref<Member | null>(null);
const { showToast } = useToasts();
const { t } = useI18n();
const isRevoking = ref(false);
const revokeError = ref<string | null>(null);

const isConfirmingRevocation = computed({
    get: () => memberBeingRevoked.value !== null,
    set: (isOpen: boolean) => {
        if (!isOpen) {
            memberBeingRevoked.value = null;
        }
    },
});

const revocationMessage = computed(() => {
    return t(`sharing.revoke.message.${props.purpose}.${itemKey(props.kind)}`, { name: memberBeingRevoked.value?.name ?? '' });
});

function confirmRevocation(member: Member): void {
    revokeError.value = null;
    memberBeingRevoked.value = member;
}

/** Type of sharing being confirmed for a member, in `ConfirmDialog`. */
const pendingShareChange = ref<{ member: Member; shareType: ShareType } | null>(null);
const isChangingShareType = ref(false);
const shareChangeError = ref<string | null>(null);

const isConfirmingShareChange = computed({
    get: () => pendingShareChange.value !== null,
    set: (isOpen: boolean) => {
        if (!isOpen) {
            pendingShareChange.value = null;
        }
    },
});

const shareChangeMessage = computed(() => {
    const change = pendingShareChange.value;

    return change ? t(`sharing.shareType.confirm.message.${change.shareType}.${itemKey(props.kind)}`, { name: change.member.name }) : '';
});

/** Puts the selector back on the current type and asks to confirm the chosen one. */
function askShareTypeChange(member: Member, event: Event): void {
    const select = event.target as HTMLSelectElement;
    const shareType = select.value as ShareType;
    select.value = member.share_type;
    shareChangeError.value = null;
    pendingShareChange.value = { member, shareType };
}

async function changeShareType(): Promise<void> {
    const change = pendingShareChange.value;
    if (!change) {
        return;
    }
    isChangingShareType.value = true;
    shareChangeError.value = null;
    try {
        await updateMemberShareType(props.kind, props.id, change.member.id, change.shareType);
        change.member.share_type = change.shareType;
        pendingShareChange.value = null;
        showToast(t(`sharing.shareType.changed.${change.shareType}`, { name: change.member.name }));
    } catch {
        shareChangeError.value = t('common.unexpectedError');
    } finally {
        isChangingShareType.value = false;
    }
}

async function load(): Promise<void> {
    hasFailed.value = false;
    try {
        access.value = await fetchItemAccess(props.kind, props.id);
        emit('membersCount', access.value.members.length);
    } catch {
        hasFailed.value = true;
    }
}

async function revoke(): Promise<void> {
    if (!memberBeingRevoked.value) {
        return;
    }
    isRevoking.value = true;
    revokeError.value = null;
    try {
        const member = memberBeingRevoked.value;
        await revokeAccess(props.kind, props.id, member.id);
        memberBeingRevoked.value = null;
        showToast(t(`sharing.revoke.done.${props.purpose}`, { name: member.name }));
        await load();
    } catch {
        revokeError.value = t('common.unexpectedError');
    } finally {
        isRevoking.value = false;
    }
}

watch(() => props.id, load, { immediate: true });
</script>

<template>
    <section class="space-y-6" :aria-label="$t('sharing.management.label')">
        <p v-if="hasFailed" class="text-sm text-red-600 dark:text-red-400">
            {{ $t('sharing.management.loadFailed') }}
            <button type="button" class="underline" @click="load">{{ $t('common.retry') }}</button>
        </p>

        <template v-else-if="access">
            <div v-if="access.pending_requests.length > 0">
                <h2 class="text-lg font-semibold">
                    {{ $t(`sharing.management.requests.${purpose}`) }}
                    <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ access.pending_requests.length }})</span>
                </h2>
                <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
                    <AccessRequestItem
                        v-for="accessRequest in access.pending_requests"
                        :key="accessRequest.id"
                        :access-request="accessRequest"
                        @decided="load"
                    />
                </ul>
            </div>

            <div>
                <h2 class="text-lg font-semibold">
                    {{ $t(`sharing.management.members.${purpose}`) }}
                    <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ access.members.length }})</span>
                </h2>
                <p v-if="access.members.length > 0" class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ inactiveReason ? $t(`sharing.shareType.inactive.${inactiveReason}.${itemKey(kind)}`) : $t(`sharing.shareType.help.${itemKey(kind)}`) }}
                </p>
                <p v-if="access.members.length === 0" class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $t('sharing.management.noMembers') }}
                </p>
                <ul v-else class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
                    <li v-for="member in access.members" :key="member.id" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ member.name }}</p>
                            <p v-if="member.granted_at" class="text-zinc-500 dark:text-zinc-400">
                                <i18n-t keypath="sharing.management.since" scope="global">
                                    <template #date><time :datetime="member.granted_at">{{ formatDateTime(member.granted_at) }}</time></template>
                                </i18n-t>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select
                                :value="member.share_type"
                                :aria-label="$t('sharing.shareType.memberLabel', { name: member.name })"
                                class="rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 py-1 text-sm text-zinc-900 dark:text-zinc-100 focus:border-brand-500 dark:focus:border-brand-400 focus:ring-1 focus:ring-brand-500 dark:focus:ring-brand-400 focus:outline-none"
                                aria-haspopup="dialog"
                                @change="askShareTypeChange(member, $event)"
                            >
                                <option v-for="shareType in shareTypes" :key="shareType" :value="shareType">
                                    {{ $t(`sharing.shareType.${shareType}.label`) }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                                aria-haspopup="dialog"
                                @click="confirmRevocation(member)"
                            >
                                {{ $t('sharing.revoke.button') }}
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </template>

        <ConfirmDialog
            v-model:open="isConfirmingShareChange"
            tone="primary"
            :title="$t('sharing.shareType.confirm.title')"
            :message="shareChangeMessage"
            :confirm-label="pendingShareChange ? $t(`sharing.shareType.confirm.button.${pendingShareChange.shareType}`) : ''"
            :processing-label="$t('sharing.shareType.confirm.processing')"
            :is-processing="isChangingShareType"
            :error="shareChangeError"
            @confirm="changeShareType"
        />

        <ConfirmDialog
            v-model:open="isConfirmingRevocation"
            :title="$t(`sharing.revoke.title.${purpose}`)"
            :message="revocationMessage"
            :confirm-label="$t('sharing.revoke.button')"
            :processing-label="$t('sharing.revoke.processing')"
            :is-processing="isRevoking"
            :error="revokeError"
            @confirm="revoke"
        />
    </section>
</template>
