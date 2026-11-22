<script setup lang="ts">
import CardMeta from '@/components/CardMeta.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemCard from '@/components/ItemCard.vue';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import type { WordGroup } from '@/types/models';

const props = defineProps<{
    wordGroup: WordGroup;
}>();

const emit = defineEmits<{
    /** The favorite star was toggled and the server confirmed it. */
    favoriteSaved: [isFavorite: boolean];
}>();

function updateFavorite(isFavorite: boolean): void {
    // The card's parent owns the data; it is kept in sync here so that the star reflects the change at once.
    props.wordGroup.is_favorite = isFavorite;
}
</script>

<template>
    <ItemCard
        :to="{ name: 'word-groups.show', params: { id: wordGroup.id } }"
        :name="wordGroup.name"
        :visibility="wordGroup.visibility"
        :accepts-contributions="wordGroup.accepts_contributions"
        :contributions-members-only="restrictsContributionsToMembers(wordGroup)"
        :updated-at="wordGroup.updated_at"
        :updated-label="$t('wordGroups.card.updatedOn')"
        :is-accessible="wordGroup.is_accessible !== false"
        :inaccessible-message="wordGroup.access_status === 'pending' ? $t('sharing.pendingRequest') : $t('wordGroups.card.becamePrivate')"
    >
        <template #meta>
            <CardMeta :creator="wordGroup.creator" :count-label="wordGroup.words_count === undefined ? undefined : $t('words.count', wordGroup.words_count)" />
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
