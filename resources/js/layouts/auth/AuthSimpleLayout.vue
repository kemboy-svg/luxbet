<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import HeroScene from '@/components/luxbet/HeroScene.vue';
import LuxBetLogo from '@/components/luxbet/LuxBetLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-plum-900 p-4 md:p-10"
    >
        <HeroScene :fade="false" />
        <div
            class="absolute inset-0 bg-plum-950/70 backdrop-blur-[2px]"
            aria-hidden="true"
        />

        <div
            class="relative z-10 w-full max-w-md rounded-[22px] border border-plum-400 bg-plum-850/95 p-6 shadow-[0_30px_80px_rgba(0,0,0,0.55)] sm:p-8"
        >
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-5">
                    <Link :href="home()" aria-label="LuxBet home">
                        <LuxBetLogo size="lg" />
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-bold">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
