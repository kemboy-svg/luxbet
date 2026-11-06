<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import GameTile from '@/components/luxbet/GameTile.vue';
import type { CatalogueGame } from '@/types/luxbet';

defineProps<{
    games: CatalogueGame[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Favourites', href: '/favourites' }],
    },
});
</script>

<template>
    <Head title="Favourites" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <h1 class="text-2xl font-bold md:text-3xl">Favourites</h1>

        <div
            v-if="games.length"
            class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4"
        >
            <GameTile v-for="game in games" :key="game.slug" :game="game" />
        </div>

        <div
            v-else
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border p-10 text-center"
        >
            <Heart class="size-8 text-muted-foreground" aria-hidden="true" />
            <p class="font-semibold">No favourites yet</p>
            <p class="max-w-sm text-sm text-muted-foreground">
                Tap the heart on any game page to keep it here for quick access.
            </p>
            <Link
                href="/games"
                class="mt-2 rounded-lg bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-4 py-2 text-sm font-bold text-gold-900"
                >Browse games</Link
            >
        </div>
    </div>
</template>
