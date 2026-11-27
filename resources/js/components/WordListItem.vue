<script setup lang="ts">
import RelativeTime from '@/components/RelativeTime.vue';
import UserLink from '@/components/UserLink.vue';
import WordVoteButtons from '@/components/WordVoteButtons.vue';
import type { Word } from '@/types/models';

/** A word of a group, read-only: renaming and deleting happen in `WordListEditor`. */
defineProps<{
    word: Word;
}>();

const emit = defineEmits<{
    updated: [word: Word];
}>();
</script>

<template>
    <li class="px-4 py-3">
        <div class="flex items-start justify-between gap-3">
            <p class="min-w-0 text-sm font-medium break-words">{{ word.name }}</p>
            <WordVoteButtons class="shrink-0" :word="word" @updated="emit('updated', $event)" />
        </div>
        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            <!-- Visitors do not get the author (undefined); null means the author deleted their account. -->
            <template v-if="word.creator !== undefined">
                <i18n-t v-if="word.creator" keypath="common.byUser" scope="global">
                    <template #user><UserLink :user="word.creator" /></template>
                </i18n-t>
                <span v-else class="italic">{{ $t('common.deletedAccount') }}</span>
                <template v-if="word.created_at"> · </template>
            </template>
            <i18n-t v-if="word.created_at" keypath="words.list.addedOn" scope="global">
                <template #date><RelativeTime :date="word.created_at" /></template>
            </i18n-t>
            <template v-if="word.updated_at && word.updated_at !== word.created_at">
                ·
                <i18n-t keypath="words.list.updatedOn" scope="global">
                    <template #date><RelativeTime :date="word.updated_at" /></template>
                </i18n-t>
            </template>
        </p>
    </li>
</template>
