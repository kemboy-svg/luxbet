<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import heroHost from '../../images/hero-host.webp';
import CategoryIcon from '@/components/luxbet/CategoryIcon.vue';
import GameTile from '@/components/luxbet/GameTile.vue';
import HeroScene from '@/components/luxbet/HeroScene.vue';
import PublicShell from '@/components/luxbet/PublicShell.vue';
import { register } from '@/routes';
import type { CatalogueCategory, CatalogueGame } from '@/types/luxbet';

defineProps<{
    categories: CatalogueCategory[];
    featuredGames: CatalogueGame[];
}>();

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth.user));
</script>

<template>
    <Head title="Play Big. Risk Nothing." />

    <PublicShell active="home">
        <!-- Hero -->
        <section class="relative h-[460px] overflow-hidden md:h-[540px]">
            <HeroScene />
            <img
                :src="heroHost"
                alt=""
                width="804"
                height="900"
                fetchpriority="high"
                class="pointer-events-none absolute right-[22%] bottom-0 z-[5] hidden h-[104%] w-auto drop-shadow-[0_20px_40px_rgba(0,0,0,0.55)] md:block lg:right-[24%]"
            />
            <div
                class="relative z-10 flex h-full max-w-[640px] flex-col justify-center px-5 pb-16 md:px-12 md:pb-10"
            >
                <span
                    class="mb-4 self-start rounded-md bg-demo px-2.5 py-1 text-xs font-bold tracking-[0.14em] text-demo-foreground"
                    >FREE DEMO · NO DEPOSITS</span
                >
                <h1
                    class="mb-4 text-5xl leading-none font-extrabold tracking-tight [text-shadow:0_4px_24px_rgba(0,0,0,0.5)] sm:text-6xl md:text-[76px]"
                >
                    Play Big.<br />
                    <span
                        class="bg-linear-to-b from-gold-100 via-gold-400 to-gold-600 bg-clip-text text-transparent"
                        >Risk Nothing.</span
                    >
                </h1>
                <p
                    class="mb-7 max-w-[480px] text-base font-medium text-[#F4ECFF] md:text-[21px] md:leading-snug"
                >
                    A premium casino experience from Mauritius — played with
                    free demo credits.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <Link
                        :href="signedIn ? '/games' : register()"
                        class="rounded-full bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-9 py-3.5 text-lg font-bold text-gold-900 shadow-[0_6px_24px_rgba(245,179,31,0.45),inset_0_1px_0_rgba(255,255,255,0.6)] transition-transform hover:-translate-y-0.5"
                    >
                        {{ signedIn ? 'Browse games' : 'Play Free Demo' }}
                    </Link>
                    <Link
                        href="/games"
                        class="px-2 py-3 font-semibold text-white hover:text-gold-300"
                        >Browse games →</Link
                    >
                </div>
            </div>
        </section>

        <!-- Categories -->
        <section
            id="categories"
            aria-label="Game categories"
            class="relative z-10 -mt-10 scroll-mt-24 px-4 md:px-8"
        >
            <ul
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-3.5"
            >
                <li v-for="category in categories" :key="category.id">
                    <Link
                        :href="`/games?category=${category.id}`"
                        class="relative flex flex-col items-center gap-2 rounded-2xl border-[1.5px] border-[#7A5520] bg-linear-to-b from-[#2A1546] to-plum-850 px-3 pt-4 pb-3.5 shadow-[0_10px_30px_rgba(0,0,0,0.45)] transition-colors hover:border-gold-500"
                    >
                        <span
                            v-if="!category.available"
                            class="absolute top-2 right-2 rounded-full bg-gold-300 px-2 py-px text-[10px] font-bold tracking-wide text-gold-900"
                            >SOON</span
                        >
                        <CategoryIcon :category="category.id" class="h-14" />
                        <span class="text-base font-semibold">{{
                            category.name
                        }}</span>
                    </Link>
                </li>
            </ul>
        </section>

        <!-- Popular games -->
        <section
            id="games"
            aria-labelledby="popular-heading"
            class="scroll-mt-24 px-4 pt-9 pb-8 md:px-8"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2
                    id="popular-heading"
                    class="text-2xl font-bold md:text-[26px]"
                >
                    Popular Games
                </h2>
                <Link
                    href="/games"
                    class="py-2 text-[15px] font-medium text-white hover:text-gold-300"
                    >View All →</Link
                >
            </div>
            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-5"
            >
                <GameTile
                    v-for="game in featuredGames"
                    :key="game.slug"
                    :game="game"
                />
            </div>
        </section>
    </PublicShell>
</template>
