<template>
    <Head title="Bem-vindo ao SoundBite" />
    <div class="min-h-screen flex flex-col relative overflow-hidden" style="background-color: var(--bg-base); color: var(--text-primary); font-family: var(--font-body)">
        <!-- Particles Background -->
        <div class="absolute inset-0 pointer-events-none opacity-20 particle-bg"></div>

        <!-- Navigation -->
        <header class="w-full p-6 lg:p-8 flex justify-end z-20 relative">
            <nav class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="px-5 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-white/10"
                    style="color: var(--text-primary)"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="px-5 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:bg-white/10"
                        style="color: var(--text-primary)"
                    >
                        Entrar
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="px-5 py-2 rounded-lg text-sm font-medium transition-all duration-300"
                        style="background-color: rgba(255, 255, 255, 0.1); color: var(--text-primary)"
                    >
                        Cadastrar
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="flex-grow flex items-center justify-center relative z-10 px-4">
            <div class="w-full max-w-3xl text-center space-y-10">
                <!-- Logo & Title -->
                <div class="space-y-4 animate-fade-in-up">
                    <h1 class="text-6xl md:text-8xl font-extrabold tracking-tight" style="font-family: var(--font-display); color: var(--accent-primary); text-shadow: 0 0 30px rgba(124, 58, 237, 0.5)">
                        SoundBite
                    </h1>
                    <p class="text-xl md:text-2xl typewriter" style="color: var(--text-muted)">
                        O Desafio Musical Definitivo
                    </p>
                </div>

                <!-- Call to Action -->
                <div class="pt-8 animate-fade-in-up delay-300">
                    <Link
                        :href="game.home.url()"
                        class="inline-block px-12 py-5 rounded-2xl font-bold text-xl uppercase tracking-widest transition-transform duration-300 hover:scale-[1.02] active:scale-95 relative overflow-hidden btn-shimmer"
                        style="background-color: var(--accent-primary); color: white; font-family: var(--font-display); box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.6)"
                    >
                        Jogar Agora
                    </Link>
                </div>

                <!-- Features / Badges -->
                <div class="pt-16 grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in-up delay-500">
                    <div class="p-6 rounded-2xl flex flex-col items-center gap-3 backdrop-blur-sm" style="background-color: var(--bg-surface); box-shadow: inset 0 0 0 1px var(--bg-elevated)">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2" style="background-color: rgba(6, 182, 212, 0.1); color: var(--accent-secondary)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>
                        </div>
                        <h3 class="font-bold text-lg" style="color: var(--text-primary)">Músicas Reais</h3>
                        <p class="text-sm text-center" style="color: var(--text-muted)">Adivinhe hits das suas playlists favoritas.</p>
                    </div>

                    <div class="p-6 rounded-2xl flex flex-col items-center gap-3 backdrop-blur-sm" style="background-color: var(--bg-surface); box-shadow: inset 0 0 0 1px var(--bg-elevated)">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2" style="background-color: rgba(251, 191, 36, 0.1); color: var(--accent-gold)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <h3 class="font-bold text-lg" style="color: var(--text-primary)">Pontuação</h3>
                        <p class="text-sm text-center" style="color: var(--text-muted)">Responda rápido para ganhar mais pontos.</p>
                    </div>

                    <div class="p-6 rounded-2xl flex flex-col items-center gap-3 backdrop-blur-sm" style="background-color: var(--bg-surface); box-shadow: inset 0 0 0 1px var(--bg-elevated)">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2" style="background-color: rgba(244, 63, 94, 0.1); color: var(--accent-error)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg" style="color: var(--text-primary)">Competição</h3>
                        <p class="text-sm text-center" style="color: var(--text-muted)">Compare seus recordes com amigos.</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer Spacer -->
        <div class="h-12 w-full"></div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import game from '@/routes/game';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<style scoped>
.particle-bg {
    background-image: radial-gradient(var(--accent-secondary) 1px, transparent 1px);
    background-size: 60px 60px;
    animation: pulseBg 8s infinite alternate;
}
@keyframes pulseBg {
    0% { opacity: 0.05; transform: scale(1); }
    100% { opacity: 0.15; transform: scale(1.05); }
}

.typewriter {
    overflow: hidden;
    white-space: nowrap;
    margin: 0 auto;
    letter-spacing: 2px;
    border-right: 2px solid var(--accent-primary);
    animation: typing 2.5s steps(40, end), blink-caret 0.8s step-end infinite;
    max-width: fit-content;
}
@keyframes typing {
    from { width: 0 }
    to { width: 100% }
}
@keyframes blink-caret {
    from, to { border-color: transparent }
    50% { border-color: var(--accent-primary) }
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

.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}
.delay-300 {
    animation-delay: 300ms;
}
.delay-500 {
    animation-delay: 500ms;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
