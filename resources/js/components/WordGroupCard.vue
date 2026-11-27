<script setup lang="ts">
import { computed } from 'vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemCard from '@/components/ItemCard.vue';
import RelativeTime from '@/components/RelativeTime.vue';
import UserLink from '@/components/UserLink.vue';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import type { WordGroup } from '@/types/models';

const props = defineProps<{
    wordGroup: WordGroup;
}>();

const emit = defineEmits<{
    /** The favorite star was toggled and the server confirmed it. */
    favoriteSaved: [isFavorite: boolean];
}>();

/**
 * Footer of a group listed by a collection, on one line: "Ajouté par X, il y a 3 jours"; guests, never told who added
 * it, get "Ajouté il y a 3 jours".
 */
const isAdderShown = computed(() => props.wordGroup.added_by !== undefined);

function updateFavorite(isFavorite: boolean): void {
    // The card's parent owns the data; it is kept in sync here so that the star reflects the change at once.
    props.wordGroup.is_favorite = isFavorite;
}
</script>

<template>
    <ItemCard
        kind="word-groups"
        :to="{ name: 'word-groups.show', params: { id: wordGroup.id } }"
        :name="wordGroup.name"
        :language="wordGroup.language"
        :visibility="wordGroup.visibility"
        :accepts-contributions="wordGroup.accepts_contributions"
        :contributions-members-only="restrictsContributionsToMembers(wordGroup)"
        :creator="wordGroup.creator"
        :count-label="wordGroup.words_count === undefined ? undefined : $t('words.count', wordGroup.words_count)"
        :updated-at="wordGroup.updated_at"
    >
        <template v-if="wordGroup.added_by !== undefined || wordGroup.added_at" #footer>
            <p v-if="isAdderShown">
                <i18n-t keypath="common.addition.by" scope="global">
                    <template #user>
                        <UserLink v-if="wordGroup.added_by" :user="wordGroup.added_by" class="relative z-10" />
                        <span v-else class="italic">{{ $t('common.deletedAccount') }}</span>
                    </template>
                    <template #date><RelativeTime v-if="wordGroup.added_at" :date="wordGroup.added_at" /></template>
                </i18n-t>
            </p>
            <p v-else-if="wordGroup.added_at">
                <i18n-t keypath="common.addition.on" scope="global">
                    <template #date><RelativeTime :date="wordGroup.added_at" /></template>
                </i18n-t>
            </p>
        </template>
        <template v-if="wordGroup.is_favorite !== undefined" #favorite>
            <FavoriteButton
                kind="word-groups"
                :id="wordGroup.id"
                :name="wordGroup.name"
                :model-value="wordGroup.is_favorite"
                @update:model-value="updateFavorite"
                @saved="emit('favoriteSaved', $event)"
            />
        </template>
    </ItemCard>
</template>
