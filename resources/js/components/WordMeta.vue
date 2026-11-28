<script setup lang="ts">
import { computed } from 'vue';
import RelativeTime from '@/components/RelativeTime.vue';
import UserLink from '@/components/UserLink.vue';
import type { Word } from '@/types/models';

/**
 * Who added a word and when, then, once renamed, who modified it and when, one line each: "Ajouté par X, il y a 2 mois",
 * "Modifié par Y, à l'instant". "Modifié à l'instant" when the editor is unknown (renamed before it was recorded, or account
 * deleted), and for visitors, who get no names. Shown in the dialog of `WordInfoButton`.
 */
const props = defineProps<{
    word: Word;
}>();

/** Visitors do not get the names (undefined); a null author deleted their account. */
const showsNames = computed(() => props.word.creator !== undefined);
const isModified = computed(() => props.word.updated_at !== null && props.word.updated_at !== props.word.created_at);
</script>

<template>
    <div class="space-y-1">
        <p>
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
        </p>
        <p v-if="isModified && word.updated_at">
            <i18n-t v-if="showsNames && word.updater" keypath="common.modification.by" scope="global">
                <template #user><UserLink :user="word.updater" /></template>
                <template #date><RelativeTime :date="word.updated_at" /></template>
            </i18n-t>
            <i18n-t v-else keypath="common.modification.on" scope="global">
                <template #date><RelativeTime :date="word.updated_at" /></template>
            </i18n-t>
        </p>
    </div>
</template>
