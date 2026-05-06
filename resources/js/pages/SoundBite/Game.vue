<template>
    <div class="min-h-screen flex flex-col items-center py-4 md:py-10 px-3 md:px-4 relative overflow-hidden" style="background-color: var(--bg-base); color: var(--text-primary); font-family: var(--font-body)">
        <!-- Particles -->
        <div class="absolute inset-0 pointer-events-none opacity-20 particle-bg"></div>

        <div class="z-10 w-full max-w-2xl flex flex-col gap-3 md:gap-8">
            <!-- Header -->
            <header class="flex justify-between items-center bg-surface p-3 md:p-6 rounded-xl md:rounded-2xl border border-elevated" style="background-color: var(--bg-surface); border-color: var(--bg-elevated)">
                <div class="flex flex-col">
                    <span class="text-xs md:text-sm uppercase tracking-widest text-muted" style="color: var(--text-muted)">Rodada</span>
                    <span class="text-xl md:text-3xl font-bold" style="font-family: var(--font-display); color: var(--accent-secondary)">{{ roundNumber }} / {{ totalSongs }}</span>
                </div>
                <div class="flex gap-4 md:gap-6 items-center">
                    <!-- <Link href="/game/create-playlist" class="text-xs uppercase font-bold tracking-widest hover:text-white transition-colors hidden md:block" style="color: var(--accent-primary)">+ Criar Playlist</Link> -->
                    <div class="flex flex-col items-end">
                        <span class="text-xs md:text-sm uppercase tracking-widest text-muted" style="color: var(--text-muted)">Pontuação</span>
                        <span class="text-2xl md:text-4xl font-extrabold score-display" style="font-family: var(--font-display); color: var(--accent-gold)">{{ displayScore }}</span>
                    </div>
                </div>
            </header>

            <!-- Visualizer & Timer -->
            <div class="flex flex-col items-center gap-2 md:gap-4 py-3 md:py-8">
                <!-- Waveform Bars -->
                <div class="flex gap-1 md:gap-2 h-16 md:h-32 items-end justify-center w-full">
                    <div
                        v-for="i in numBars"
                        :key="i"
                        class="w-3 md:w-4 rounded-t-sm transition-all duration-75 origin-bottom"
                        :style="{
                            height: isPlaying ? (Math.random() * 80 + 20) + '%' : '10%',
                            backgroundColor: isPlaying ? 'var(--accent-primary)' : 'var(--bg-elevated)'
                        }"
                    ></div>
                </div>

                <!-- Timer Bar -->
                <div class="w-full h-2 md:h-3 rounded-full bg-elevated overflow-hidden mt-2 md:mt-4 relative" style="background-color: var(--bg-elevated)">
                    <div
                        class="h-full transition-all duration-[1000ms] ease-linear"
                        :style="{
                            width: (secondsRevealed / 10 * 100) + '%',
                            backgroundColor: timerColor
                        }"
                    ></div>
                </div>
                <div class="text-xs md:text-sm text-muted" style="color: var(--text-muted)">
                    Ouvindo: {{ secondsRevealed }}s (-{{ (secondsRevealed - 1) * 100 }} pts)
                </div>
                <div class="text-xs md:text-sm font-bold mt-1 md:mt-2" style="color: var(--accent-gold)">
                    ⏱️ {{ elapsedSeconds }}s (-{{ elapsedSeconds * 5 }} pts)
                </div>
            </div>

            <!-- Controls -->
            <div class="flex flex-col gap-3 md:gap-4">
                <div class="relative w-full">
                    <input
                        v-model="guessInput"
                        @focus="showAutocomplete = true"
                        @input="filterTitles"
                        @keyup.enter="submitGuess"
                        type="text"
                        placeholder="Qual é a música?"
                        class="w-full px-4 py-3 md:px-6 md:py-5 rounded-xl md:rounded-2xl text-base md:text-xl outline-none transition-all duration-300 border-2 border-transparent text-white"
                        style="background-color: var(--bg-surface); box-shadow: inset 0 0 0 2px var(--bg-elevated);"
                        :class="{'input-glow': showAutocomplete && autocompleteEnabled}"
                    />

                    <!-- Autocomplete -->
                    <ul
                        v-if="showAutocomplete && autocompleteEnabled && filteredTitles.length > 0"
                        class="absolute z-20 w-full mt-2 rounded-xl overflow-hidden max-h-60 overflow-y-auto shadow-2xl autocomplete-list"
                        style="background-color: var(--bg-elevated); border: 1px solid var(--bg-surface)"
                    >
                        <li
                            v-for="title in filteredTitles"
                            :key="title"
                            @click="selectTitle(title)"
                            class="px-5 py-3 cursor-pointer hover:bg-opacity-80 transition-colors"
                            style="border-bottom: 1px solid rgba(255,255,255,0.05)"
                            :style="{ backgroundColor: guessInput === title ? 'var(--accent-primary)' : 'transparent' }"
                        >
                            {{ title }}
                        </li>
                    </ul>
                </div>

                <!-- Help Cards -->
                <div class="grid grid-cols-3 gap-2 md:gap-4 w-full mt-1 md:mt-2">
                    <button
                        @click="addSecond"
                        :disabled="secondsRevealed >= 10 || isPlaying || loading"
                        class="flex flex-col items-center justify-center p-2 md:p-4 rounded-xl md:rounded-2xl transition-all duration-200 hover:-translate-y-1 hover:shadow-lg disabled:opacity-50 disabled:hover:translate-y-0"
                        style="background-color: var(--bg-surface); border: 1px solid var(--bg-elevated);"
                    >
                        <span class="text-lg md:text-3xl mb-0.5 md:mb-2">🎵</span>
                        <span class="font-bold text-[10px] md:text-sm uppercase tracking-wider leading-tight text-center" style="color: var(--accent-secondary); font-family: var(--font-display)">+1 Seg</span>
                        <span class="text-[9px] md:text-xs text-muted mt-0.5 md:mt-1 hidden md:block" style="color: var(--text-muted)">-100 pts</span>
                    </button>

                    <button
                        @click="playAudioSnippet"
                        :disabled="isPlaying || loading"
                        class="flex flex-col items-center justify-center p-2 md:p-4 rounded-xl md:rounded-2xl transition-all duration-200 hover:-translate-y-1 hover:shadow-lg disabled:opacity-50 disabled:hover:translate-y-0"
                        style="background-color: var(--bg-surface); border: 1px solid var(--bg-elevated);"
                    >
                        <span class="text-lg md:text-3xl mb-0.5 md:mb-2">🔁</span>
                        <span class="font-bold text-[10px] md:text-sm uppercase tracking-wider leading-tight text-center" style="color: var(--text-primary); font-family: var(--font-display)">Replay</span>
                        <span class="text-[9px] md:text-xs text-muted mt-0.5 md:mt-1 hidden md:block" style="color: var(--text-muted)">Grátis</span>
                    </button>

                    <button
                        @click="buyAutocomplete"
                        :disabled="autocompleteEnabled || loading"
                        class="flex flex-col items-center justify-center p-2 md:p-4 rounded-xl md:rounded-2xl transition-all duration-200 hover:-translate-y-1 hover:shadow-lg disabled:opacity-50 disabled:hover:translate-y-0 relative overflow-hidden"
                        :style="{
                            backgroundColor: autocompleteEnabled ? 'var(--bg-elevated)' : 'var(--bg-surface)',
                            border: autocompleteEnabled ? '1px solid transparent' : '1px solid var(--accent-primary)'
                        }"
                    >
                        <span class="text-lg md:text-3xl mb-0.5 md:mb-2">💡</span>
                        <span class="font-bold text-[10px] md:text-sm uppercase tracking-wider leading-tight text-center" :style="{ color: autocompleteEnabled ? 'var(--text-muted)' : 'var(--accent-primary)' }" style="font-family: var(--font-display)">
                            {{ autocompleteEnabled ? 'Ativo' : 'Auto' }}
                        </span>
                        <span class="text-[9px] md:text-xs text-muted mt-0.5 md:mt-1 hidden md:block" style="color: var(--text-muted)" v-if="!autocompleteEnabled">-200 pts</span>
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 md:gap-4 w-full mt-2 md:mt-4 flex-row">
                    <button
                        @click="submitGuess"
                        :disabled="!guessInput || isPlaying || loading"
                        class="flex-1 py-3 md:py-5 rounded-xl md:rounded-2xl font-black text-sm md:text-xl uppercase tracking-wider transition-all duration-200 hover:scale-[0.98] active:scale-95 disabled:opacity-50 shadow-[0_0_20px_rgba(34,197,94,0.2)]"
                        style="background-color: var(--accent-success); color: #09090f; font-family: var(--font-display)"
                    >
                        ✓ Confirmar
                    </button>
                    <button
                        @click="skipRound"
                        :disabled="isPlaying || loading"
                        class="flex-1 py-3 md:py-5 rounded-xl md:rounded-2xl font-bold text-sm md:text-lg uppercase tracking-wider transition-all duration-200 hover:scale-[0.98] active:scale-95 disabled:opacity-50"
                        style="background-color: transparent; color: var(--accent-error); border: 2px solid var(--accent-error); font-family: var(--font-display)"
                    >
                        ✗ Passar
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden YT Player -->
        <div id="yt-player" class="hidden"></div>

        <!-- Wrong Guess Dialog -->
        <div v-if="showErrorDialog" class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity" style="background-color: rgba(9, 9, 15, 0.7)">
            <div class="w-full max-w-sm rounded-2xl p-6 text-center shadow-2xl transform transition-all error-dialog" style="background-color: var(--bg-surface); border: 2px solid var(--accent-error); box-shadow: 0 0 20px rgba(244, 63, 94, 0.3)">
                <h3 class="text-3xl font-extrabold mb-2 uppercase" style="color: var(--accent-error); font-family: var(--font-display)">Errou!</h3>
                <p class="text-lg mb-6" style="color: var(--text-muted)">
                    Você perdeu <strong style="color: var(--accent-error)">{{ lastScoreLost }} pontos</strong>.
                </p>
                <div class="flex gap-4">
                    <button v-if="canRevealMoreState" @click="closeErrorDialogAndAddSecond" class="flex-1 py-3 rounded-xl font-bold transition-transform hover:scale-[0.98] active:scale-95" style="background-color: var(--accent-secondary); color: var(--bg-base); font-family: var(--font-display)">+1 Segundo</button>
                    <button @click="closeErrorDialog" class="flex-1 py-3 rounded-xl font-bold transition-transform hover:scale-[0.98] active:scale-95" style="background-color: var(--bg-elevated); color: var(--text-primary); font-family: var(--font-display)">Tentar Novamente</button>
                </div>
            </div>
        </div>

        <ResultOverlay :show="showResult" :result="roundResult" @next="startNextRound" />
        <GameOverOverlay :show="showGameOver" :finalScore="finalScore" :highScores="highScores" :hasMoreSongs="hasMoreSongs" @playAgain="resetAndPlayAgain" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import gsap from 'gsap';
import ResultOverlay from './ResultOverlay.vue';
import GameOverOverlay from './GameOverOverlay.vue';

const page = usePage();
const playlistTitles = page.props.playlistTitles || [];
const initialScore = page.props.totalScore || 0;

const totalScore = ref(initialScore);
const displayScore = ref(initialScore);

const roundNumber = ref(0);
const totalSongs = ref(0);
const currentVideoId = ref(null);
const currentSeek = ref(0);

const secondsRevealed = ref(1);
const isPlaying = ref(false);
const loading = ref(false);

const guessInput = ref('');
const showAutocomplete = ref(false);
const filteredTitles = ref([]);

const numBars = ref(20);
const forceUpdateBars = ref(0); // to trigger reactivity

const showResult = ref(false);
const roundResult = ref(null);

const showErrorDialog = ref(false);
const lastScoreLost = ref(0);
const canRevealMoreState = ref(false);

const showGameOver = ref(false);
const finalScore = ref(0);
const highScores = ref([]);
const hasMoreSongs = ref(false);

const autocompleteEnabled = ref(false);
const elapsedSeconds = ref(0);
const roundStartTime = ref(null);
const timerInterval = ref(null);

let player = null;
let playTimeout = null;
let barsInterval = null;

const timerColor = computed(() => {
    const ratio = secondsRevealed.value / 10;
    const hue = 120 - (ratio * 120);
    return `hsl(${hue}, 100%, 50%)`;
});

onMounted(() => {
    if (window.innerWidth < 768) {
        numBars.value = 12;
    }

    barsInterval = setInterval(() => {
        if (isPlaying.value) {
            forceUpdateBars.value++;
        }
    }, 100);

    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    window.onYouTubeIframeAPIReady = () => {
        player = new window.YT.Player('yt-player', {
            height: '0',
            width: '0',
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    };

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            showAutocomplete.value = false;
        }
    });

    startNextRound();
});

onUnmounted(() => {
    if (playTimeout) clearTimeout(playTimeout);
    if (barsInterval) clearInterval(barsInterval);
});

watch(totalScore, (newVal) => {
    gsap.to(displayScore, { value: newVal, duration: 1, ease: 'power1.out', onUpdate: () => {
        displayScore.value = Math.ceil(displayScore.value);
    }});
});

const onPlayerReady = (event) => {
    if (currentVideoId.value) {
        playAudioSnippet();
    }
};

const onPlayerStateChange = (event) => {
    if (event.data === window.YT.PlayerState.PLAYING) {
        isPlaying.value = true;
        if (playTimeout) clearTimeout(playTimeout);

        // Se a rodada acabou (mostrando resultado ou tela de game over), deixa a música tocar livremente
        if (!showResult.value && !showGameOver.value) {
            playTimeout = setTimeout(() => {
                player.pauseVideo();
                isPlaying.value = false;
            }, secondsRevealed.value * 1000);
        }
    } else if (event.data === window.YT.PlayerState.PAUSED || event.data === window.YT.PlayerState.ENDED) {
        isPlaying.value = false;
        if (playTimeout) clearTimeout(playTimeout);
    }
};

const playAudioSnippet = () => {
    if (!player || typeof player.loadVideoById !== 'function') return;
    isPlaying.value = false;
    player.loadVideoById({
        videoId: currentVideoId.value,
        startSeconds: currentSeek.value
    });
};

const addSecond = () => {
    if (secondsRevealed.value < 10) {
        secondsRevealed.value++;
        playAudioSnippet();
    }
};

const closeErrorDialog = () => {
    showErrorDialog.value = false;
};

const closeErrorDialogAndAddSecond = () => {
    showErrorDialog.value = false;
    addSecond();
};

const filterTitles = () => {
    if (!autocompleteEnabled.value) {
        filteredTitles.value = [];
        return;
    }
    const q = guessInput.value.trim().toLowerCase();
    // Sugerir o nome da música somente após 4 caracteres/letras digitados
    if (q.length < 4) {
        filteredTitles.value = [];
        return;
    }
    filteredTitles.value = playlistTitles.filter(t => t.toLowerCase().includes(q)).slice(0, 5);
};

const selectTitle = (t) => {
    guessInput.value = t;
    showAutocomplete.value = false;
};

const getCsrfToken = () => {
    // Inertia apps usually don't have a meta tag with csrf, instead we can get it from document cookies or inertia props
    // We can also let Laravel handle it via useHttp or similar, but fetch requires it.
    // In Laravel 11+, csrf is automatically added to axios. So we can use axios if available, or just fetch if we don't strictly need CSRF or we can parse cookie.
    // In Inertia Laravel, Axios is pre-configured usually in resources/js/bootstrap.js. Let's try native fetch.
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const playSound = (type) => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        if (type === 'success') {
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
            gainNode.gain.setValueAtTime(0.5, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
            oscillator.start(audioCtx.currentTime);
            oscillator.stop(audioCtx.currentTime + 0.5);
        } else if (type === 'error') {
            oscillator.type = 'sawtooth';
            oscillator.frequency.setValueAtTime(200, audioCtx.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(100, audioCtx.currentTime + 0.3);
            gainNode.gain.setValueAtTime(0.5, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
            oscillator.start(audioCtx.currentTime);
            oscillator.stop(audioCtx.currentTime + 0.3);
        }
    } catch (e) {
        console.error("Audio API not supported or error playing sound", e);
    }
};

const buyAutocomplete = async () => {
    loading.value = true;
    try {
        const response = await fetch('/game/enable-autocomplete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (data.success) {
            totalScore.value = data.totalScore;
            autocompleteEnabled.value = true;
            filterTitles();
        }
    } catch (e) {
        console.error(e);
    }
    loading.value = false;
};

const startNextRound = async () => {
    showResult.value = false;
    loading.value = true;
    secondsRevealed.value = 1;
    guessInput.value = '';
    autocompleteEnabled.value = false;

    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }

    try {
        const response = await fetch('/game/start-round', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();

        if (data.finished) {
            finalScore.value = data.totalScore;
            highScores.value = data.highScores || [];
            hasMoreSongs.value = data.hasMoreSongs || false;
            showGameOver.value = true;
            loading.value = false;
            return;
        }

        currentVideoId.value = data.videoId;
        currentSeek.value = data.seekPosition;
        totalSongs.value = data.totalSongs;
        roundNumber.value = data.roundNumber;
        totalScore.value = data.totalScore;

        setTimeout(() => {
            playAudioSnippet();
            loading.value = false;

            // start timer
            elapsedSeconds.value = 0;
            roundStartTime.value = Date.now();
            timerInterval.value = setInterval(() => {
                elapsedSeconds.value = Math.floor((Date.now() - roundStartTime.value) / 1000);
            }, 1000);
        }, 500);

    } catch (e) {
        console.error(e);
        loading.value = false;
    }
};

const submitGuess = async () => {
    if (!guessInput.value) return;
    loading.value = true;

    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }

    try {
        const response = await fetch('/game/guess', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                answer: guessInput.value,
                secondsRevealed: secondsRevealed.value,
                timeElapsed: elapsedSeconds.value
            })
        });
        const data = await response.json();

        if (data.correct) {
            playSound('success');
            totalScore.value = data.totalScore;
            roundResult.value = {
                correct: true,
                song: data.song,
                scoreGained: data.scoreGained
            };
            showResult.value = true;

            // Tocar de fundo infinito
            if (player && typeof player.playVideo === 'function') {
                player.playVideo();
            }
        } else {
            playSound('error');
            totalScore.value = data.totalScore;
            lastScoreLost.value = data.scoreLost;
            canRevealMoreState.value = data.canRevealMore;
            showErrorDialog.value = true;
            guessInput.value = '';

            // Anim dialog
            setTimeout(() => {
                gsap.fromTo('.error-dialog', { scale: 0.8, opacity: 0 }, { scale: 1, opacity: 1, duration: 0.4, ease: 'back.out(1.7)' });
            }, 50);

            // resume timer if incorrect
            if (canRevealMoreState.value) {
                // adjust start time so elapsedSeconds continues correctly
                roundStartTime.value = Date.now() - (elapsedSeconds.value * 1000);
                timerInterval.value = setInterval(() => {
                    elapsedSeconds.value = Math.floor((Date.now() - roundStartTime.value) / 1000);
                }, 1000);
            }
        }
    } catch (e) {
        console.error(e);
    }
    loading.value = false;
};

const skipRound = async () => {
    loading.value = true;

    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }

    try {
        const response = await fetch('/game/skip', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();

        roundResult.value = {
            correct: false,
            song: data.song,
            scoreGained: 0
        };
        showResult.value = true;

        // Tocar de fundo infinito
        if (player && typeof player.playVideo === 'function') {
            player.playVideo();
        }
    } catch (e) {
        console.error(e);
    }
    loading.value = false;
};

const resetAndPlayAgain = async () => {
    loading.value = true;
    try {
        await fetch('/game/reset-round', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        showGameOver.value = false;
        startNextRound();
    } catch (e) {
        console.error(e);
        loading.value = false;
    }
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

.input-glow {
    box-shadow: 0 0 15px var(--accent-primary), inset 0 0 0 2px var(--accent-secondary) !important;
}
</style>
