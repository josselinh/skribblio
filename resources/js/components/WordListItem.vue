<script setup lang="ts">
import RelativeTime from '@/components/RelativeTime.vue';
import UserLink from '@/components/UserLink.vue';
import WordVoteButtons from '@/components/WordVoteButtons.vue';
import type { Word } from '@/types/models';

/**
 * A word of a group, read-only: renaming and deleting happen in `WordListEditor`. On phones, the name and its votes,
 * then the author and the dates on a second line; from `md`, everything on one line.
 */
defineProps<{
    word: Word;
}>();

const emit = defineEmits<{
    updated: [word: Word];
}>();
</script>

<template>
    <li class="flex items-start justify-between gap-3 px-4 py-3 md:items-center md:py-2">
        <div class="min-w-0 md:flex md:flex-1 md:items-baseline md:gap-4">
            <p class="text-sm font-medium break-words md:w-56 md:shrink-0">{{ word.name }}</p>
            <p class="mt-0.5 text-xs text-zinc-500 md:mt-0 dark:text-zinc-400">
                <!-- Visitors do not get the author (undefined); null means the author deleted their account. -->
                <i18n-t
                    v-if="word.creator !== undefined"
                    :keypath="word.created_at ? 'common.addition.by' : 'common.byUser'"
                    scope="global"
                >
                    <template #user>
                        <UserLink v-if="word.creator" :user="word.creator" />
                        <span v-else class="italic">{{ $t('common.deletedAccount') }}</span>
                    </template>
                    <template #date><RelativeTime v-if="word.created_at" :date="word.created_at" /></template>
                </i18n-t>
                <i18n-t v-else-if="word.created_at" keypath="common.addition.on" scope="global">
                    <template #date><RelativeTime :date="word.created_at" /></template>
                </i18n-t>
                <template v-if="word.updated_at && word.updated_at !== word.created_at">
                    ·
                    <i18n-t keypath="words.list.updatedOn" scope="global">
                        <template #date><RelativeTime :date="word.updated_at" /></template>
                    </i18n-t>
                </template>
            </p>
        </div>
        <WordVoteButtons class="shrink-0" :word="word" @updated="emit('updated', $event)" />
    </li>
</template>
