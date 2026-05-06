<template>
    <div class="min-h-screen flex items-center justify-center relative overflow-hidden" style="background-color: var(--bg-base); color: var(--text-primary); font-family: var(--font-body)">
        <!-- Particles -->
        <div class="absolute inset-0 pointer-events-none opacity-20 particle-bg"></div>

        <div class="z-10 w-full max-w-md p-6 text-center space-y-8">
            <h1 class="text-6xl font-extrabold tracking-tight" style="font-family: var(--font-display); color: var(--accent-primary); text-shadow: 0 0 20px rgba(124, 58, 237, 0.5)">SoundBite</h1>

            <p class="text-lg typewriter" style="color: var(--text-muted)">
                Quantos segundos você precisa?
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="relative">
                    <input
                        v-model="form.url"
                        type="url"
                        required
                        placeholder="Cole a URL da playlist do YouTube..."
                        class="w-full px-5 py-4 rounded-xl outline-none transition-all duration-300 border-2 border-transparent text-white"
                        style="background-color: var(--bg-surface); box-shadow: inset 0 0 0 2px var(--bg-elevated);"
                        @focus="focused = true"
                        @blur="focused = false"
                        :class="{'input-glow': focused}"
                    />
                    <div v-if="form.errors.url" class="text-sm mt-2 text-left" style="color: var(--accent-error)">
                        {{ form.errors.url }}
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-4 rounded-xl font-bold text-lg uppercase tracking-wider transition-transform duration-200 hover:scale-[0.97] active:scale-90 relative overflow-hidden btn-shimmer"
                    style="background-color: var(--accent-primary); color: white; font-family: var(--font-display)"
                >
                    <span v-if="!form.processing">Iniciar Jogo</span>
                    <span v-else>Carregando...</span>
                </button>
            </form>

            <div class="flex items-center justify-center space-x-4">
                <div class="h-px w-full" style="background-color: var(--bg-elevated)"></div>
                <span class="text-sm font-semibold tracking-wider text-muted" style="color: var(--text-muted)">OU</span>
                <div class="h-px w-full" style="background-color: var(--bg-elevated)"></div>
            </div>

            <Link
                href="/game/create-playlist"
                class="block w-full py-4 rounded-xl font-bold text-lg uppercase tracking-wider transition-transform duration-200 hover:scale-[0.97] active:scale-90"
                style="background-color: transparent; border: 2px solid var(--accent-secondary); color: var(--accent-secondary); font-family: var(--font-display)"
            >
                CRIAR NOVA PLAYLIST
            </Link>

            <div v-if="recentPlaylists && recentPlaylists.length > 0" class="mt-8">
                <h2 class="text-xl font-bold mb-4 text-left" style="color: var(--text-secondary)">Playlists Jogadas Recentemente</h2>
                <div class="space-y-3 max-h-64 overflow-y-auto custom-scrollbar pr-2">
                    <button
                        v-for="playlist in recentPlaylists"
                        :key="playlist.id"
                        @click="playRecent(playlist.url)"
                        class="w-full flex items-center p-3 rounded-lg transition-all duration-200 text-left group hover:scale-[1.02]"
                        style="background-color: var(--bg-surface); border: 1px solid var(--bg-elevated);"
                    >
                        <img
                            v-if="playlist.thumbnail"
                            :src="playlist.thumbnail"
                            class="w-12 h-12 rounded object-cover mr-4"
                            alt="Thumbnail"
                        />
                        <div v-else class="w-12 h-12 rounded mr-4 bg-gray-700 flex items-center justify-center">
                            <span class="text-xl">🎵</span>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <h3 class="font-semibold truncate text-white group-hover:text-[var(--accent-primary)] transition-colors">{{ playlist.name }}</h3>
                            <p class="text-xs text-gray-400 mt-1">Jogada {{ playlist.played_count }} vezes</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    recentPlaylists: Array
});

const form = useForm({
    url: ''
});

const focused = ref(false);

const submit = () => {
    form.post('/game/playlist');
};

const playRecent = (url) => {
    form.url = url;
    submit();
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

.typewriter {
    overflow: hidden;
    white-space: nowrap;
    margin: 0 auto;
    letter-spacing: 1px;
    border-right: 2px solid var(--accent-secondary);
    animation: typing 2s steps(30, end), blink-caret 0.75s step-end infinite;
}
@keyframes typing {
    from { width: 0 }
    to { width: 100% }
}
@keyframes blink-caret {
    from, to { border-color: transparent }
    50% { border-color: var(--accent-secondary) }
}

.input-glow {
    box-shadow: 0 0 15px var(--accent-primary), inset 0 0 0 2px var(--accent-secondary) !important;
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
