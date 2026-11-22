<script setup lang="ts">
import CardMeta from '@/components/CardMeta.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemCard from '@/components/ItemCard.vue';
import { restrictsContributionsToMembers } from '@/lib/contributions';
import type { GroupCollection } from '@/types/models';

const props = defineProps<{
    groupCollection: GroupCollection;
}>();

const emit = defineEmits<{
    /** The favorite star was toggled and the server confirmed it. */
    favoriteSaved: [isFavorite: boolean];
}>();

function updateFavorite(isFavorite: boolean): void {
    // The card's parent owns the data; it is kept in sync here so that the star reflects the change at once.
    props.groupCollection.is_favorite = isFavorite;
}
</script>

<template>
    <ItemCard
        :to="{ name: 'group-collections.show', params: { id: groupCollection.id } }"
        :name="groupCollection.name"
        :visibility="groupCollection.visibility"
        :accepts-contributions="groupCollection.accepts_contributions"
        :contributions-members-only="restrictsContributionsToMembers(groupCollection)"
        :updated-at="groupCollection.updated_at"
        :updated-label="$t('groupCollections.card.updatedOn')"
        :is-accessible="groupCollection.is_accessible !== false"
        :inaccessible-message="groupCollection.access_status === 'pending' ? $t('sharing.pendingRequest') : $t('groupCollections.card.becamePrivate')"
    >
        <template #meta>
            <CardMeta
                :creator="groupCollection.creator"
                :count-label="groupCollection.word_groups_count === undefined ? undefined : $t('wordGroups.count', groupCollection.word_groups_count)"
            />
        </template>
        <template v-if="groupCollection.is_favorite !== undefined && groupCollection.access_status !== 'pending'" #favorite>
            <FavoriteButton
                kind="group-collections"
                :id="groupCollection.id"
                :name="groupCollection.name"
                :model-value="groupCollection.is_favorite"
                @update:model-value="updateFavorite"
                @saved="emit('favoriteSaved', $event)"
            />
        </template>
    </ItemCard>
</template>
