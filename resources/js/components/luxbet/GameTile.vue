<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { CatalogueGame } from '@/types/luxbet';

const { game, size = 'md' } = defineProps<{
    game: CatalogueGame;
    size?: 'md' | 'lg';
}>();

const tag = computed(() => {
    switch (game.status) {
        case 'playable':
            return { text: 'DEMO', class: 'bg-demo text-demo-foreground' };
        case 'coming_soon':
            return { text: 'COMING SOON', class: 'bg-gold-300 text-gold-900' };
        default:
            return {
                text: 'DEMO NOT OFFERED',
                class: 'bg-plum-200 text-plum-900',
            };
    }
});

const subtitle = computed(() =>
    game.status === 'coming_soon'
        ? `${game.category_label} · Pending approval`
        : `${game.category_label} · ${game.provider.is_simulator ? 'Simulator' : game.provider.name}`,
);
</script>

<template>
    <Link
        :href="`/games/${game.slug}`"
        :aria-label="`${game.name}, ${tag.text.toLowerCase()}`"
        class="group relative flex items-center justify-center overflow-hidden rounded-2xl border-2 shadow-[0_10px_30px_rgba(0,0,0,0.45)] transition-transform duration-200 hover:-translate-y-1 focus-visible:-translate-y-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400"
        :class="size === 'lg' ? 'h-72 md:h-96' : 'h-44'"
        :style="{ background: game.background, borderColor: game.border }"
    >
        <span
            class="absolute top-2 left-2 rounded px-1.5 py-0.5 text-[10px] font-bold tracking-wider"
            :class="tag.class"
            >{{ tag.text }}</span
        >
        <span
            class="px-3 text-center font-display leading-[0.95] tracking-wide whitespace-pre-line text-[#FFD447] transition-transform duration-200 [text-shadow:0_3px_0_#8A2A00,0_0_18px_rgba(0,0,0,0.6)] group-hover:scale-105"
            :class="size === 'lg' ? 'text-6xl md:text-7xl' : 'text-[34px]'"
            >{{ game.tile_title }}</span
        >
        <span
            class="absolute inset-x-0 bottom-2 text-center text-xs font-semibold text-plum-200"
            >{{ subtitle }}</span
        >
    </Link>
</template>
