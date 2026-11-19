<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { duplicateWordGroup, fetchWordGroup, updateWordGroup } from '@/api/wordGroups';
import { fetchWords } from '@/api/words';
import AddWordForm from '@/components/AddWordForm.vue';
import AppLayout from '@/components/AppLayout.vue';
import AccessManagement from '@/components/AccessManagement.vue';
import FavoriteButton from '@/components/FavoriteButton.vue';
import SharePanel from '@/components/SharePanel.vue';
import ExportPanel from '@/components/ExportPanel.vue';
import EditableNameVisibility, { type NameVisibilityPayload } from '@/components/EditableNameVisibility.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import StatusMessage from '@/components/StatusMessage.vue';
import WordRow from '@/components/WordRow.vue';
import { consumeHistoryStatus } from '@/lib/historyStatus';
import type { PaginatedResponse } from '@/types/api';
import type { Word, WordGroup } from '@/types/models';

const route = useRoute();
const router = useRouter();

const wordGroupId = computed(() => String(route.params.id));
const currentPage = computed(() => {
    const page = Number(route.query.page);

    return Number.isInteger(page) && page > 0 ? page : 1;
});

const wordGroup = ref<WordGroup | null>(null);
const groupStatus = ref<'loading' | 'loaded' | 'not-found' | 'failed'>('loading');

const words = ref<PaginatedResponse<Word> | null>(null);
const areWordsLoading = ref(false);
const haveWordsFailed = ref(false);

/** Message sent by the previous page (e.g. after a duplication), read again on each group since the page is reused. */
const flashStatus = ref<string | null>(null);

const isDuplicating = ref(false);
const hasDuplicationFailed = ref(false);

/** Copies the group into a group of the current user and opens the copy. */
async function duplicate(): Promise<void> {
    isDuplicating.value = true;
    hasDuplicationFailed.value = false;
    try {
        const copy = await duplicateWordGroup(wordGroupId.value);
        await router.push({
            name: 'word-groups.show',
            params: { id: copy.id },
            state: { status: 'Groupe dupliqué. Il est privé et fermé aux contributions.' },
        });
    } catch {
        hasDuplicationFailed.value = true;
    } finally {
        isDuplicating.value = false;
    }
}

async function loadWordGroup(): Promise<void> {
    flashStatus.value = consumeHistoryStatus();
    groupStatus.value = 'loading';
    try {
        wordGroup.value = await fetchWordGroup(wordGroupId.value);
        groupStatus.value = 'loaded';
    } catch (error) {
        groupStatus.value = isAxiosError(error) && error.response?.status === 404 ? 'not-found' : 'failed';
    }
}

async function loadWords(): Promise<void> {
    areWordsLoading.value = true;
    haveWordsFailed.value = false;
    try {
        words.value = await fetchWords(wordGroupId.value, currentPage.value);
    } catch {
        haveWordsFailed.value = true;
    } finally {
        areWordsLoading.value = false;
    }
}

/** Reloads the words too: opening or closing contributions changes what the user can do with them. */
async function saveWordGroup(payload: NameVisibilityPayload): Promise<void> {
    wordGroup.value = await updateWordGroup(wordGroupId.value, payload);
    await loadWords();
}

/** A new word is listed first: show page 1 so that it is visible. */
async function onWordAdded(): Promise<void> {
    if (currentPage.value === 1) {
        await loadWords();
    } else {
        await router.push({ query: { ...route.query, page: undefined } });
    }
}

function onWordUpdated(updatedWord: Word): void {
    if (words.value) {
        words.value.data = words.value.data.map((word) => (word.id === updatedWord.id ? updatedWord : word));
    }
}

/** Reloads the page, or goes back one page when the deleted word was the last one on it. */
async function onWordDeleted(): Promise<void> {
    if (words.value?.data.length === 1 && currentPage.value > 1) {
        await router.push({ query: { ...route.query, page: String(currentPage.value - 1) } });
    } else {
        await loadWords();
    }
}

watch(wordGroupId, loadWordGroup, { immediate: true });
watch([wordGroupId, currentPage], loadWords, { immediate: true });
</script>

<template>
    <AppLayout>
        <RouterLink :to="{ name: 'dashboard' }" class="text-sm text-zinc-500 hover:text-zinc-900">
            ← Retour à mes groupes
        </RouterLink>
        <StatusMessage class="mt-4" :message="flashStatus" />

        <p v-if="groupStatus === 'loading'" class="mt-4 text-zinc-500">Chargement…</p>

        <div v-else-if="groupStatus === 'not-found'" class="mt-4">
            <h1 class="text-2xl font-semibold">Groupe introuvable</h1>
            <p class="mt-2 text-zinc-500">Ce groupe n'existe pas ou vous n'y avez pas accès.</p>
        </div>

        <p v-else-if="groupStatus === 'failed'" class="mt-4 text-red-600">
            Impossible de charger le groupe.
            <button type="button" class="underline" @click="loadWordGroup">Réessayer</button>
        </p>

        <template v-else-if="wordGroup">
            <div class="mt-2">
                <EditableNameVisibility
                    :name="wordGroup.name"
                    :visibility="wordGroup.visibility"
                    :accepts-contributions="wordGroup.accepts_contributions"
                    kind="word-groups"
                    :can-edit="wordGroup.can.update"
                    name-label="Nom du groupe"
                    :save="saveWordGroup"
                >
                    <template v-if="wordGroup.is_favorite !== undefined" #after-title>
                        <FavoriteButton
                            v-model="wordGroup.is_favorite"
                            kind="word-groups"
                            :id="wordGroup.id"
                            :name="wordGroup.name"
                        />
                    </template>
                </EditableNameVisibility>
            </div>

            <ExportPanel class="mt-4" kind="word-groups" :id="wordGroup.id" />
            <div v-if="wordGroup.can.duplicate" class="mt-4">
                <button
                    type="button"
                    :disabled="isDuplicating"
                    class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-xs hover:bg-zinc-50 disabled:opacity-60"
                    @click="duplicate"
                >
                    {{ isDuplicating ? 'Duplication…' : 'Dupliquer' }}
                </button>
                <p class="mt-1 text-xs text-zinc-500">
                    Crée une copie privée de ce groupe et de ses mots, dont vous êtes l’auteur.
                </p>
                <p v-if="hasDuplicationFailed" role="alert" class="mt-1 text-sm text-red-600">
                    Impossible de dupliquer le groupe. Réessayez.
                </p>
            </div>
            <div v-if="wordGroup.can.share" class="mt-4">
                <SharePanel kind="word-groups" :id="wordGroup.id" />
            </div>
            <AccessManagement
                v-if="wordGroup.can.manage_access && wordGroup.visibility === 'private'"
                class="mt-8"
                kind="word-groups"
                :id="wordGroup.id"
            />

            <AddWordForm v-if="wordGroup.can.add_word" class="mt-6" :word-group-id="wordGroup.id" @added="onWordAdded" />
            <p v-else class="mt-6 text-sm text-zinc-500">
                Ce groupe n’est pas ouvert aux contributions : seul son créateur peut modifier ses mots.
            </p>

            <p v-if="haveWordsFailed" class="mt-6 text-red-600">
                Impossible de charger les mots.
                <button type="button" class="underline" @click="loadWords">Réessayer</button>
            </p>

            <p v-else-if="words && words.meta.total === 0" class="mt-6 rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">
                Ce groupe ne contient encore aucun mot.
            </p>

            <template v-else-if="words">
                <p class="mt-6 text-sm text-zinc-500">{{ words.meta.total }} mot{{ words.meta.total > 1 ? 's' : '' }}</p>
                <div
                    class="mt-2 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-xs"
                    :class="{ 'opacity-60': areWordsLoading }"
                >
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-medium tracking-wide text-zinc-500 uppercase">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">Mot</th>
                                <th scope="col" class="px-4 py-2.5">Créé par</th>
                                <th scope="col" class="px-4 py-2.5">Créé le</th>
                                <th scope="col" class="px-4 py-2.5">Modifié le</th>
                                <th scope="col" class="px-4 py-2.5"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <WordRow
                                v-for="word in words.data"
                                :key="word.id"
                                :word="word"
                                @updated="onWordUpdated"
                                @deleted="onWordDeleted"
                            />
                        </tbody>
                    </table>
                </div>
                <PaginationNav class="mt-4" :current-page="words.meta.current_page" :last-page="words.meta.last_page" />
            </template>
        </template>
    </AppLayout>
</template>
