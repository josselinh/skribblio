<script setup lang="ts">
import { computed } from 'vue';
import RelativeTime from '@/components/RelativeTime.vue';
import ScrollingRow from '@/components/ScrollingRow.vue';
import UserLink from '@/components/UserLink.vue';
import type { Word } from '@/types/models';

/**
 * Who added a word and when, then, once renamed, who modified it and when: "Ajouté par X, il y a 2 mois · Modifié par Y,
 * à l'instant", on one line that scrolls horizontally when it does not fit (`ScrollingRow`). "Modifié à l'instant" when the editor is unknown (renamed
 * before it was recorded, or account deleted), and for visitors, who get no names. Shared by the read-only list
 * (`WordListItem`) and the editor (`WordListEditor`).
 */
const props = defineProps<{
    word: Word;
}>();

/** Visitors do not get the names (undefined); a null author deleted their account. */
const showsNames = computed(() => props.word.creator !== undefined);
const isModified = computed(() => props.word.updated_at !== null && props.word.updated_at !== props.word.created_at);
</script>

<template>
    <ScrollingRow class="text-xs whitespace-nowrap text-zinc-500 dark:text-zinc-400">
        <span>
            <i18n-t v-if="showsNames" :keypath="word.created_at ? 'common.addition.by' : 'common.byUser'" scope="global">
                <template #user>
                    <UserLink v-if="word.creator" :user="word.creator" />
                    <span v-else class="italic">{{ $t('common.deletedAccount') }}</span>
                </template>
                <template #date><RelativeTime v-if="word.created_at" :date="word.created_at" /></template>
            </i18n-t>
            <i18n-t v-else-if="word.created_at" keypath="common.addition.on" scope="global">
                <template #date><RelativeTime :date="word.created_at" /></template>
            </i18n-t>
        </span>
        <template v-if="isModified && word.updated_at">
            <span class="mx-1.5 text-zinc-300 dark:text-zinc-600" aria-hidden="true">·</span>
            <span>
                <i18n-t v-if="showsNames && word.updater" keypath="common.modification.by" scope="global">
                    <template #user><UserLink :user="word.updater" /></template>
                    <template #date><RelativeTime :date="word.updated_at" /></template>
                </i18n-t>
                <i18n-t v-else keypath="common.modification.on" scope="global">
                    <template #date><RelativeTime :date="word.updated_at" /></template>
                </i18n-t>
            </span>
        </template>
    </ScrollingRow>
</template>
