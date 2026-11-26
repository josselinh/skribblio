<script setup lang="ts">
import { computed } from 'vue';
import UserLink from '@/components/UserLink.vue';
import { formatDateTime } from '@/lib/format';
import { itemKey } from '@/lib/i18n';
import type { FavoriteKind, User } from '@/types/models';

/**
 * Details of a card, in the order of the page title of an item (`ItemHeader`): "Créé par Bob", then
 * "Mise à jour le …". The creator's name links to their page, above the card's stretched link.
 */
const props = defineProps<{
    kind: FavoriteKind;
    creator?: Pick<User, 'id' | 'name'>;
    updatedAt?: string | null;
}>();

const item = computed(() => itemKey(props.kind));
</script>

<template>
    <div v-if="creator || updatedAt" class="space-y-0.5 text-sm text-zinc-600 dark:text-zinc-400">
        <p v-if="creator">
            <i18n-t :keypath="`editing.createdBy.${item}`" scope="global">
                <template #user><UserLink :user="creator" class="relative z-10" /></template>
            </i18n-t>
        </p>
        <p v-if="updatedAt" class="text-zinc-500 dark:text-zinc-400">
            <i18n-t keypath="editing.updatedOn" scope="global">
                <template #date><time :datetime="updatedAt">{{ formatDateTime(updatedAt) }}</time></template>
            </i18n-t>
        </p>
    </div>
</template>
