<script setup lang="ts">
import { computed } from 'vue';
import WordGroupSearch from '@/components/WordGroupSearch.vue';
import type { WordGroup } from '@/types/models';

defineProps<{
    error?: string;
}>();

/** Selected groups. */
const selected = defineModel<WordGroup[]>({ required: true });

const selectedIds = computed(() => selected.value.map((wordGroup) => wordGroup.id));

function add(wordGroup: WordGroup): void {
    if (!selectedIds.value.includes(wordGroup.id)) {
        selected.value = [...selected.value, wordGroup];
    }
}

function remove(wordGroup: WordGroup): void {
    selected.value = selected.value.filter((group) => group.id !== wordGroup.id);
}
</script>

<template>
    <div>
        <label for="word-group-search" class="block text-sm font-medium">Groupes</label>
        <p class="text-sm text-zinc-500">Vos groupes et les groupes publics des autres utilisateurs. Au moins un.</p>

        <WordGroupSearch
            class="mt-2"
            input-id="word-group-search"
            :excluded-ids="selectedIds"
            :invalid="Boolean(error)"
            @select="add"
        />

        <ul v-if="selected.length > 0" class="mt-3 flex flex-wrap gap-2" aria-label="Groupes sélectionnés">
            <li
                v-for="wordGroup in selected"
                :key="wordGroup.id"
                class="inline-flex items-center gap-1 rounded-full border border-zinc-300 bg-zinc-50 py-1 pr-1 pl-3 text-sm"
            >
                {{ wordGroup.name }}
                <button
                    type="button"
                    class="rounded-full px-1.5 text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900"
                    :aria-label="`Retirer ${wordGroup.name}`"
                    @click="remove(wordGroup)"
                >
                    ×
                </button>
            </li>
        </ul>

        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
