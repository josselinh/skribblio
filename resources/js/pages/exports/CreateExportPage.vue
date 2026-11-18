<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { fetchExportComposition, type ExportComposition } from '@/api/exports';
import { createGroupCollection } from '@/api/groupCollections';
import { fetchGroupCollectionSearchResults } from '@/api/search';
import FormField from '@/components/FormField.vue';
import AppLayout from '@/components/AppLayout.vue';
import ItemSearch from '@/components/ItemSearch.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import VisibilityField from '@/components/VisibilityField.vue';
import WordGroupSearch from '@/components/WordGroupSearch.vue';
import WordListExport from '@/components/WordListExport.vue';
import { useForm } from '@/composables/useForm';
import type { ValidationErrorResponse } from '@/types/api';
import type { GroupCollection, Visibility, WordGroup } from '@/types/models';

/**
 * Composes an export from groups and collections, then lets the user save the composition as a
 * new collection. Nothing is saved until then.
 */
const router = useRouter();

const selectedWordGroups = ref<WordGroup[]>([]);
const selectedGroupCollections = ref<GroupCollection[]>([]);

const selectedWordGroupIds = computed(() => selectedWordGroups.value.map((wordGroup) => wordGroup.id));
const selectedGroupCollectionIds = computed(() => selectedGroupCollections.value.map((collection) => collection.id));
const hasSelection = computed(() => selectedWordGroupIds.value.length + selectedGroupCollectionIds.value.length > 0);

const composition = ref<ExportComposition | null>(null);
const isComposing = ref(false);
const compositionError = ref<string | null>(null);
let latestComposition = 0;

/** Recomputes the words of the selection, ignoring outdated responses. */
async function compose(): Promise<void> {
    if (!hasSelection.value) {
        composition.value = null;
        compositionError.value = null;

        return;
    }
    const compositionId = ++latestComposition;
    isComposing.value = true;
    compositionError.value = null;
    try {
        const result = await fetchExportComposition(selectedWordGroupIds.value, selectedGroupCollectionIds.value);
        if (compositionId === latestComposition) {
            composition.value = result;
        }
    } catch (error) {
        if (compositionId === latestComposition) {
            compositionError.value =
                isAxiosError<ValidationErrorResponse>(error) && error.response?.status === 422
                    ? error.response.data.message
                    : 'Impossible de charger les mots.';
        }
    } finally {
        if (compositionId === latestComposition) {
            isComposing.value = false;
        }
    }
}

watch([selectedWordGroupIds, selectedGroupCollectionIds], compose);

function searchGroupCollections(query: string): Promise<GroupCollection[]> {
    return fetchGroupCollectionSearchResults({ q: query, author: 'all', page: 1, perPage: 10 }).then(
        (results) => results.data,
    );
}

function addWordGroup(wordGroup: WordGroup): void {
    if (!selectedWordGroupIds.value.includes(wordGroup.id)) {
        selectedWordGroups.value = [...selectedWordGroups.value, wordGroup];
    }
}

function addGroupCollection(groupCollection: GroupCollection): void {
    if (!selectedGroupCollectionIds.value.includes(groupCollection.id)) {
        selectedGroupCollections.value = [...selectedGroupCollections.value, groupCollection];
    }
}

function removeWordGroup(id: string): void {
    selectedWordGroups.value = selectedWordGroups.value.filter((wordGroup) => wordGroup.id !== id);
}

function removeGroupCollection(id: string): void {
    selectedGroupCollections.value = selectedGroupCollections.value.filter((collection) => collection.id !== id);
}

const hiddenGroupsNote = computed(() => {
    const count = composition.value?.hidden_word_groups_count ?? 0;
    if (count === 0) {
        return null;
    }

    return count === 1
        ? '1 groupe des collections choisies vous est masqué : ses mots ne sont pas exportés.'
        : `${count} groupes des collections choisies vous sont masqués : leurs mots ne sont pas exportés.`;
});

const wordGroupsCount = computed(() => composition.value?.word_group_ids.length ?? 0);

const form = useForm({ name: '', visibility: 'private' as Visibility });

/** Saves the groups of the composition (collections flattened into their groups) as a new collection. */
async function saveAsGroupCollection(): Promise<void> {
    if (!composition.value) {
        return;
    }
    const wordGroupIds = composition.value.word_group_ids;
    await form.submit(async (data) => {
        const groupCollection = await createGroupCollection({ ...data, word_group_ids: wordGroupIds });
        await router.push({ name: 'group-collections.show', params: { id: groupCollection.id } });
    });
}

const saveError = computed(
    () =>
        form.errors.value.word_group_ids ??
        Object.entries(form.errors.value).find(([field]) => field.startsWith('word_group_ids.'))?.[1],
);

const tagClass = 'inline-flex items-center gap-1 rounded-full border border-zinc-300 bg-zinc-50 py-1 pr-1 pl-3 text-sm';
const removeButtonClass = 'rounded-full px-1.5 text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900';
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-3xl">
            <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
                ← Retour au tableau de bord
            </RouterLink>
            <h1 class="mt-2 text-2xl font-semibold">Créer un export</h1>
            <p class="mt-1 text-zinc-500">
                Choisissez des groupes et des collections : leurs mots sont réunis dans une liste prête à coller dans
                Skribbl.io.
            </p>

            <section
                class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm"
                aria-labelledby="export-selection-title"
            >
                <h2 id="export-selection-title" class="text-lg font-semibold">Sélection</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="export-word-group-search" class="block text-sm font-medium">Groupes</label>
                        <WordGroupSearch
                            class="mt-1"
                            input-id="export-word-group-search"
                            :excluded-ids="selectedWordGroupIds"
                            @select="addWordGroup"
                        />
                    </div>
                    <div>
                        <label for="export-group-collection-search" class="block text-sm font-medium">Collections</label>
                        <ItemSearch
                            class="mt-1"
                            input-id="export-group-collection-search"
                            :search="searchGroupCollections"
                            placeholder="Rechercher une collection…"
                            empty-message="Aucune collection trouvée."
                            :excluded-ids="selectedGroupCollectionIds"
                            @select="addGroupCollection"
                        />
                    </div>
                </div>

                <p v-if="!hasSelection" class="mt-4 text-sm text-zinc-500">Aucun groupe ni collection sélectionné.</p>

                <ul v-else class="mt-4 flex flex-wrap gap-2" aria-label="Éléments sélectionnés">
                    <li v-for="groupCollection in selectedGroupCollections" :key="groupCollection.id" :class="tagClass">
                        <span class="text-xs font-medium tracking-wide text-zinc-500 uppercase">Collection</span>
                        {{ groupCollection.name }}
                        <button
                            type="button"
                            :class="removeButtonClass"
                            :aria-label="`Retirer la collection ${groupCollection.name}`"
                            @click="removeGroupCollection(groupCollection.id)"
                        >
                            ×
                        </button>
                    </li>
                    <li v-for="wordGroup in selectedWordGroups" :key="wordGroup.id" :class="tagClass">
                        <span class="text-xs font-medium tracking-wide text-zinc-500 uppercase">Groupe</span>
                        {{ wordGroup.name }}
                        <button
                            type="button"
                            :class="removeButtonClass"
                            :aria-label="`Retirer le groupe ${wordGroup.name}`"
                            @click="removeWordGroup(wordGroup.id)"
                        >
                            ×
                        </button>
                    </li>
                </ul>
            </section>

            <template v-if="hasSelection">
                <section
                    class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm"
                    aria-labelledby="export-result-title"
                >
                    <h2 id="export-result-title" class="text-lg font-semibold">Export</h2>

                    <p v-if="compositionError" role="alert" class="mt-2 text-sm text-red-600">
                        {{ compositionError }}
                        <button type="button" class="underline" @click="compose">Réessayer</button>
                    </p>
                    <p v-else-if="!composition" class="mt-2 text-sm text-zinc-500">Chargement…</p>
                    <p v-else-if="composition.words.length === 0" class="mt-2 text-sm text-zinc-500">
                        Aucun mot à exporter.
                        <template v-if="hiddenGroupsNote">{{ hiddenGroupsNote }}</template>
                    </p>
                    <WordListExport
                        v-else
                        class="mt-2"
                        :words="composition.words"
                        :note="hiddenGroupsNote"
                        :is-loading="isComposing"
                    />
                </section>

                <section
                    v-if="composition && wordGroupsCount > 0"
                    class="mt-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm"
                    aria-labelledby="export-save-title"
                >
                    <h2 id="export-save-title" class="text-lg font-semibold">Enregistrer comme collection</h2>
                    <p class="mt-1 text-sm text-zinc-500">
                        La nouvelle collection contiendra les {{ wordGroupsCount }} groupe{{ wordGroupsCount > 1 ? 's' : '' }}
                        de cette sélection. Elle ne suivra pas les changements des collections choisies.
                    </p>

                    <form class="mt-4 space-y-6" novalidate @submit.prevent="saveAsGroupCollection">
                        <FormField
                            id="name"
                            v-model="form.data.name"
                            label="Nom de la collection"
                            :error="form.errors.value.name"
                            required
                        />
                        <VisibilityField v-model="form.data.visibility" :error="form.errors.value.visibility" />
                        <p v-if="saveError" role="alert" class="text-sm text-red-600">{{ saveError }}</p>
                        <PrimaryButton :is-loading="form.isProcessing.value || isComposing">
                            Créer la collection
                        </PrimaryButton>
                    </form>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
