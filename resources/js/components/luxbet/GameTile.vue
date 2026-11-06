<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { ShowcaseGame } from '@/types/luxbet';

defineProps<{
    game: ShowcaseGame;
    href: NonNullable<InertiaLinkProps['href']>;
}>();
</script>

<template>
    <Link
        :href="href"
        class="group relative flex h-44 items-center justify-center overflow-hidden rounded-2xl border-2 shadow-[0_10px_30px_rgba(0,0,0,0.45)] transition-transform duration-200 hover:-translate-y-1 focus-visible:-translate-y-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400"
        :style="{ background: game.background, borderColor: game.border }"
    >
        <span
            v-if="game.available"
            class="absolute top-2 left-2 rounded bg-demo px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-demo-foreground"
            >DEMO</span
        >
        <span
            v-else
            class="absolute top-2 left-2 rounded bg-gold-300 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-gold-900"
            >COMING SOON</span
        >
        <span
            class="px-3 text-center font-display text-[34px] leading-[0.95] tracking-wide whitespace-pre-line text-[#FFD447] transition-transform duration-200 [text-shadow:0_3px_0_#8A2A00,0_0_18px_rgba(0,0,0,0.6)] group-hover:scale-105"
            >{{ game.title }}</span
        >
        <span
            class="absolute inset-x-0 bottom-2 text-center text-xs font-semibold text-plum-200"
            >{{ game.subtitle }}</span
        >
    </Link>
</template>
