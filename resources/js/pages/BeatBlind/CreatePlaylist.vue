<template>
    <div class="min-h-screen flex flex-col items-center py-10 px-4 relative overflow-hidden" style="background-color: var(--bg-base); color: var(--text-primary); font-family: var(--font-body)">
        <!-- Particles -->
        <div class="absolute inset-0 pointer-events-none opacity-20 particle-bg"></div>

        <div class="z-10 w-full max-w-4xl flex flex-col gap-8">
            <header class="flex justify-between items-center bg-surface p-6 rounded-2xl border border-elevated" style="background-color: var(--bg-surface); border-color: var(--bg-elevated)">
                <div class="flex flex-col">
                    <h1 class="text-3xl font-extrabold" style="font-family: var(--font-display); color: var(--accent-primary)">Criar Nova Playlist</h1>
                    <p class="text-muted" style="color: var(--text-muted)">Busque e adicione músicas para a sua própria playlist</p>
                </div>
                <div>
                    <Link href="/game" class="text-sm uppercase tracking-widest hover:text-white transition-colors" style="color: var(--text-muted)">Voltar</Link>
                </div>
            </header>

            <div class="grid grid-cols-1 gap-8 items-start">
                <!-- Search Section -->
                <div class="space-y-6">
                    <div class="bg-surface p-6 rounded-2xl border border-elevated" style="background-color: var(--bg-surface); border-color: var(--bg-elevated)">
                        <form @submit.prevent="search" class="space-y-4">
                            <div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    required
                                    placeholder="Buscar artistas ou músicas..."
                                    class="w-full px-5 py-4 rounded-xl outline-none transition-all duration-300 border-2 border-transparent text-white"
                                    style="background-color: var(--bg-elevated); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1);"
                                />
                            </div>
                            <button
                                type="submit"
                                :disabled="searching || !searchQuery"
                                class="w-full py-3 rounded-xl font-bold uppercase tracking-wider transition-transform duration-200 hover:scale-[0.98] active:scale-95 disabled:opacity-50"
                                style="background-color: var(--accent-secondary); color: var(--bg-base); font-family: var(--font-display)"
                            >
                                <span v-if="!searching">Pesquisar</span>
                                <span v-else>Pesquisando...</span>
                            </button>
                        </form>
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar pr-2">
                        <div
                            v-for="video in searchResults"
                            :key="video.videoId"
                            class="flex items-center p-3 rounded-lg transition-all duration-200"
                            style="background-color: var(--bg-surface); border: 1px solid var(--bg-elevated);"
                        >
                            <img :src="video.thumbnail" class="w-16 h-12 rounded object-cover mr-4" alt="Thumbnail" />
                            <div class="flex-1 min-w-0 mr-4">
                                <h3 class="font-semibold text-sm truncate text-white" :title="video.title">{{ video.title }}</h3>
                            </div>
                            <button
                                @click="addTrack(video)"
                                class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all hover:scale-110 active:scale-95"
                                style="background-color: var(--accent-primary); color: white;"
                            >
                                +
                            </button>
                        </div>

                        <!-- Load More Button -->
                        <div v-if="nextPageToken" class="pt-4">
                            <button
                                @click="loadMore"
                                :disabled="searching"
                                class="w-full py-2 rounded-xl text-sm font-bold uppercase tracking-wider transition-all hover:brightness-110 active:scale-95 disabled:opacity-50"
                                style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--bg-elevated)"
                            >
                                <span v-if="!searching">Buscar mais 10</span>
                                <span v-else>Buscando...</span>
                            </button>
                        </div>
                    </div>
                    <div v-else-if="hasSearched && searchResults.length === 0" class="text-center p-6" style="color: var(--text-muted)">
                        Nenhum resultado encontrado.
                    </div>
                </div>

                <!-- Playlist Section -->
                <div class="space-y-6">
                    <div class="bg-surface p-6 rounded-2xl border border-elevated" style="background-color: var(--bg-surface); border-color: var(--bg-elevated)">
                        <form @submit.prevent="finalizePlaylist" class="space-y-4">
                            <div>
                                <input
                                    v-model="playlistTitle"
                                    type="text"
                                    required
                                    placeholder="Título da Playlist..."
                                    class="w-full px-5 py-4 rounded-xl outline-none transition-all duration-300 border-2 border-transparent text-white"
                                    style="background-color: var(--bg-elevated); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1);"
                                />
                            </div>
                            <div class="text-sm" style="color: var(--text-muted)">
                                {{ selectedTracks.length }} músicas selecionadas
                            </div>
                            <button
                                @click="finalizePlaylist"
                                type="button"
                                :disabled="finalizing || selectedTracks.length === 0 || !playlistTitle"
                                class="w-full py-4 rounded-xl font-bold text-lg uppercase tracking-wider transition-transform duration-200 hover:scale-[0.98] active:scale-95 disabled:opacity-50 btn-shimmer relative overflow-hidden"
                                style="background-color: var(--accent-success); color: #09090f; font-family: var(--font-display)"
                            >
                                <span v-if="!finalizing">Finalizar e Jogar</span>
                                <span v-else>Processando...</span>
                            </button>
                        </form>
                    </div>

                    <!-- Selected Tracks -->
                    <div v-if="selectedTracks.length > 0" class="space-y-3 max-h-[28rem] overflow-y-auto custom-scrollbar pr-2">
                        <div
                            v-for="(track, index) in selectedTracks"
                            :key="track.videoId + '-' + index"
                            class="flex items-center p-3 rounded-lg transition-all duration-200"
                            style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2);"
                        >
                            <img :src="track.thumbnail" class="w-16 h-12 rounded object-cover mr-4" alt="Thumbnail" />
                            <div class="flex-1 min-w-0 mr-4">
                                <h3 class="font-semibold text-sm truncate text-white" :title="track.title">{{ track.title }}</h3>
                            </div>
                            <button
                                @click="removeTrack(index)"
                                class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all hover:scale-110 active:scale-95"
                                style="background-color: transparent; border: 2px solid var(--accent-error); color: var(--accent-error);"
                            >
                                ✗
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const searchQuery = ref('');
const searching = ref(false);
const hasSearched = ref(false);
const searchResults = ref([]);
const nextPageToken = ref(null);

const playlistTitle = ref('');
const selectedTracks = ref([]);
const finalizing = ref(false);

const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const search = async () => {
    if (!searchQuery.value) return;

    searching.value = true;
    hasSearched.value = true;
    nextPageToken.value = null;

    try {
        const response = await fetch('/game/search-youtube', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ query: searchQuery.value })
        });

        if (response.ok) {
            const data = await response.json();
            searchResults.value = data.items || [];
            nextPageToken.value = data.nextPageToken;
        } else {
            console.error('Failed to search');
            searchResults.value = [];
        }
    } catch (e) {
        console.error(e);
        searchResults.value = [];
    }

    searching.value = false;
};

const loadMore = async () => {
    if (!nextPageToken.value || searching.value) return;

    searching.value = true;

    try {
        const response = await fetch('/game/search-youtube', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                query: searchQuery.value,
                pageToken: nextPageToken.value
            })
        });

        if (response.ok) {
            const data = await response.json();
            searchResults.value = [...searchResults.value, ...(data.items || [])];
            nextPageToken.value = data.nextPageToken;
        } else {
            console.error('Failed to load more');
        }
    } catch (e) {
        console.error(e);
    }

    searching.value = false;
};

const addTrack = (video) => {
    // Avoid adding duplicates
    if (!selectedTracks.value.some(t => t.videoId === video.videoId)) {
        selectedTracks.value.push(video);
    }
};

const removeTrack = (index) => {
    selectedTracks.value.splice(index, 1);
};

const finalizeForm = useForm({
    title: '',
    tracks: []
});

const finalizePlaylist = () => {
    if (selectedTracks.value.length === 0 || !playlistTitle.value) return;

    finalizing.value = true;
    finalizeForm.title = playlistTitle.value;
    finalizeForm.tracks = selectedTracks.value;

    finalizeForm.post('/game/play-custom-playlist', {
        onFinish: () => {
            finalizing.value = false;
        }
    });
};
</script>

<style scoped>
.particle-bg {
    background-image: radial-gradient(var(--accent-secondary) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: pulseBg 4s infinite alternate;
}
@keyframes pulseBg {
    0% { opacity: 0.1; }
    100% { opacity: 0.3; }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: var(--bg-elevated);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--accent-secondary);
    border-radius: 10px;
}

.btn-shimmer::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
    transform: rotate(45deg);
    animation: shimmer 3s infinite;
}
@keyframes shimmer {
    0% { transform: translateX(-100%) rotate(45deg); }
    100% { transform: translateX(100%) rotate(45deg); }
}
</style>
