<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-md transition-opacity duration-500"
        :style="{ backgroundColor: 'rgba(9, 9, 15, 0.9)' }"
    >
        <div
            class="w-full max-w-lg rounded-2xl p-8 text-center shadow-2xl flex flex-col items-center game-over-dialog"
            style="background-color: var(--bg-surface); border: 2px solid var(--accent-primary); box-shadow: 0 0 40px rgba(124, 58, 237, 0.4)"
        >
            <h2
                class="text-5xl font-extrabold mb-2 uppercase tracking-widest game-over-title"
                style="color: var(--accent-primary); font-family: var(--font-display)"
            >
                Fim de Jogo
            </h2>

            <div class="text-3xl font-bold mt-6 mb-8 flex flex-col items-center justify-center game-over-score" style="color: var(--accent-gold); font-family: var(--font-display)">
                <span class="text-sm mb-1 uppercase tracking-widest" style="color: var(--text-muted)">Sua Pontuação</span>
                <span class="text-6xl score-final">{{ finalScore }}</span>
            </div>

            <div class="w-full bg-elevated rounded-xl p-6 mb-8 game-over-list" style="background-color: var(--bg-elevated)">
                <h3 class="text-xl font-bold mb-4 uppercase tracking-wider" style="color: var(--text-primary); font-family: var(--font-display)">Top 5 Pontuações</h3>
                <ul class="space-y-3">
                    <li
                        v-for="(score, index) in highScores"
                        :key="index"
                        class="flex justify-between items-center px-4 py-3 rounded-lg transition-all duration-300"
                        :style="{ 
                            backgroundColor: index === highScores.indexOf(finalScore) ? 'rgba(16, 185, 129, 0.15)' : (index === 0 ? 'rgba(251, 191, 36, 0.1)' : 'rgba(255,255,255,0.02)'),
                            border: index === highScores.indexOf(finalScore) ? '1px solid var(--accent-success)' : '1px solid transparent'
                        }"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-bold" :style="{ color: index === 0 && index !== highScores.indexOf(finalScore) ? 'var(--accent-gold)' : 'var(--text-muted)' }">#{{ index + 1 }}</span>
                            <span v-if="index === highScores.indexOf(finalScore)" class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded" style="background-color: var(--accent-success); color: #000">Você</span>
                        </div>
                        <span class="font-bold text-xl" :style="{ color: index === highScores.indexOf(finalScore) ? 'var(--accent-success)' : (index === 0 ? 'var(--accent-gold)' : 'var(--text-primary)'), fontFamily: 'var(--font-display)' }">{{ score }} pts</span>
                    </li>
                </ul>
            </div>

            <div class="w-full flex gap-4 mt-2 flex-col md:flex-row">
                <button
                    v-if="hasMoreSongs"
                    @click="$emit('playAgain')"
                    class="flex-1 py-4 rounded-xl font-bold text-lg uppercase tracking-wider transition-transform duration-200 hover:scale-[0.97] active:scale-90"
                    style="background-color: var(--accent-secondary); color: var(--bg-base); font-family: var(--font-display)"
                >
                    Jogar de novo
                </button>

                <button
                    @click="goHome"
                    class="flex-1 py-4 rounded-xl font-bold text-lg uppercase tracking-wider transition-transform duration-200 hover:scale-[0.97] active:scale-90"
                    style="background-color: var(--bg-surface); border: 2px solid var(--text-muted); color: var(--text-primary); font-family: var(--font-display)"
                >
                    Voltar p/ Home
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch, nextTick } from 'vue';
import gsap from 'gsap';
import confetti from 'canvas-confetti';

const props = defineProps({
    show: Boolean,
    finalScore: Number,
    highScores: Array,
    hasMoreSongs: Boolean
});

const emit = defineEmits(['playAgain']);

const goHome = () => {
    window.location.href = '/game';
};

watch(() => props.show, async (newVal) => {
    if (newVal) {
        await nextTick();

        // Confetti
        confetti({
            particleCount: 200,
            spread: 100,
            origin: { y: 0.5 },
            colors: ['#7c3aed', '#06b6d4', '#fbbf24']
        });

        const tl = gsap.timeline();

        tl.fromTo('.game-over-dialog',
            { scale: 0.9, opacity: 0 },
            { scale: 1, opacity: 1, duration: 0.5, ease: 'back.out(1.5)' }
        )
        .fromTo('.game-over-title',
            { y: -20, opacity: 0 },
            { y: 0, opacity: 1, duration: 0.4 },
            "-=0.2"
        )
        .fromTo('.score-final',
            { textContent: 0 },
            { textContent: props.finalScore, duration: 1.5, ease: 'power2.out', snap: { textContent: 1 }, onUpdate: function() {
                this.targets()[0].innerHTML = Math.ceil(this.targets()[0].textContent);
            }},
            "-=0.2"
        )
        .fromTo('.game-over-list li',
            { x: -20, opacity: 0 },
            { x: 0, opacity: 1, duration: 0.3, stagger: 0.1 },
            "-=1"
        );
    }
});
</script>
