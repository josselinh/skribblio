<script setup lang="ts">
import CardMeta from '@/components/CardMeta.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import ItemCard from '@/components/ItemCard.vue';
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
        :updated-at="groupCollection.updated_at"
        updated-label="Modifiée le"
        :is-accessible="groupCollection.is_accessible !== false"
        inaccessible-message="Cette collection est devenue privée : vous n'y avez plus accès."
    >
        <template #meta>
            <CardMeta
                :creator="groupCollection.creator"
                :count="groupCollection.word_groups_count"
                :count-labels="['groupe', 'groupes']"
            />
        </template>
        <template v-if="groupCollection.is_favorite !== undefined" #favorite>
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
