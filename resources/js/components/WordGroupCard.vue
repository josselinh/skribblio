<script setup lang="ts">
import { computed } from 'vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemCard from '@/components/ItemCard.vue';
import UserLink from '@/components/UserLink.vue';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import { formatDateTime } from '@/lib/format';
import type { WordGroup } from '@/types/models';

const props = defineProps<{
    wordGroup: WordGroup;
}>();

const emit = defineEmits<{
    /** The favorite star was toggled and the server confirmed it. */
    favoriteSaved: [isFavorite: boolean];
}>();

/**
 * Footer of a group listed by a collection: "Ajouté par X", then "Le …" on the next line; guests, never told who added
 * it, get "Ajouté le …".
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
        :is-accessible="wordGroup.is_accessible !== false"
        :inaccessible-message="wordGroup.access_status === 'pending' ? $t('sharing.pendingRequest') : $t('wordGroups.card.becamePrivate')"
    >
        <template v-if="wordGroup.added_by !== undefined || wordGroup.added_at" #footer>
            <p v-if="isAdderShown">
                <i18n-t keypath="wordGroups.card.addition.by" scope="global">
                    <template #user>
                        <UserLink v-if="wordGroup.added_by" :user="wordGroup.added_by" class="relative z-10" />
                        <span v-else class="italic">{{ $t('common.deletedAccount') }}</span>
                    </template>
                </i18n-t>
            </p>
            <p v-if="wordGroup.added_at">
                <i18n-t :keypath="isAdderShown ? 'wordGroups.card.addition.date' : 'wordGroups.card.addition.on'" scope="global">
                    <template #date><time :datetime="wordGroup.added_at">{{ formatDateTime(wordGroup.added_at) }}</time></template>
                </i18n-t>
            </p>
        </template>
        <template v-if="wordGroup.is_favorite !== undefined && wordGroup.access_status !== 'pending'" #favorite>
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
