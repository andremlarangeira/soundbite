<template>
    <div 
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-300 backdrop-blur-sm"
        :style="{ backgroundColor: 'rgba(9, 9, 15, 0.85)' }"
    >
        <div 
            class="w-full max-w-md rounded-2xl p-8 text-center shadow-2xl transform transition-all"
            :style="{ 
                backgroundColor: 'var(--bg-surface)', 
                border: `2px solid ${result.correct ? 'var(--accent-success)' : 'var(--accent-error)'}`,
                boxShadow: `0 0 30px ${result.correct ? 'var(--accent-success)' : 'var(--accent-error)'}40`
            }"
        >
            <h2 
                class="text-4xl font-extrabold mb-2 uppercase tracking-widest result-title"
                :style="{ color: result.correct ? 'var(--accent-success)' : 'var(--accent-error)', fontFamily: 'var(--font-display)' }"
            >
                {{ result.correct ? 'Acertou! 🎉' : 'Errou...' }}
            </h2>

            <div v-if="!result.correct && result.song" class="text-lg mb-6" style="color: var(--text-muted)">
                Era: <strong style="color: var(--text-primary)">{{ result.song.title }}</strong>
            </div>

            <div v-if="result.song" class="relative w-full aspect-video rounded-xl overflow-hidden mb-6 shadow-lg">
                <img :src="result.song.thumbnail" class="w-full h-full object-cover transition-all duration-1000" :class="{'grayscale': !result.correct && justRevealed}" />
            </div>

            <div v-if="result.correct" class="text-2xl font-bold mb-8 flex flex-col items-center justify-center" style="color: var(--accent-gold); font-family: var(--font-display)">
                <span class="text-sm mb-1" style="color: var(--text-muted)">Pontos Ganhos</span>
                +<span class="score-counter text-5xl">{{ result.scoreGained }}</span>
            </div>
            <div v-else-if="!result.correct && result.scoreGained === 0" class="text-2xl font-bold mb-8" style="color: var(--accent-error); font-family: var(--font-display)">
                0 Pontos
            </div>

            <button 
                @click="$emit('next')"
                class="w-full py-4 rounded-xl font-bold text-lg uppercase tracking-wider transition-transform duration-200 hover:scale-[0.97] active:scale-90 btn-pulse"
                :style="{ backgroundColor: 'var(--accent-primary)', color: 'white', fontFamily: 'var(--font-display)' }"
            >
                Próxima Música
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import gsap from 'gsap';
import confetti from 'canvas-confetti';

const props = defineProps({
    show: Boolean,
    result: Object // { correct, song, scoreGained }
});

const emit = defineEmits(['next']);
const justRevealed = ref(true);

watch(() => props.show, async (newVal) => {
    if (newVal) {
        justRevealed.value = true;
        setTimeout(() => { justRevealed.value = false; }, 500);

        await nextTick();

        if (props.result.correct) {
            // Animate title
            gsap.fromTo('.result-title', { y: -50, opacity: 0 }, { y: 0, opacity: 1, duration: 0.6, ease: 'bounce.out' });
            
            // Confetti
            confetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#7c3aed', '#06b6d4', '#22d3ee', '#fbbf24']
            });

            // Animate score
            gsap.fromTo('.score-counter', 
                { textContent: 0 }, 
                { textContent: props.result.scoreGained, duration: 1.5, ease: 'power1.out', snap: { textContent: 1 }, onUpdate: function() {
                    this.targets()[0].innerHTML = Math.ceil(this.targets()[0].textContent);
                }}
            );
        } else {
            // Shake
            gsap.fromTo('.result-title', { x: -10 }, { x: 10, duration: 0.1, yoyo: true, repeat: 5 });
        }
    }
});
</script>

<style scoped>
.btn-pulse {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(124, 58, 237, 0); }
    100% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); }
}
</style>
