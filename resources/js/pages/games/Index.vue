<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock3, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryIcon from '@/components/luxbet/CategoryIcon.vue';
import GameTile from '@/components/luxbet/GameTile.vue';
import PublicShell from '@/components/luxbet/PublicShell.vue';
import type {
    CatalogueCategory,
    CatalogueGame,
    GameCategory,
} from '@/types/luxbet';

const props = defineProps<{
    games: {
        data: CatalogueGame[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    categories: CatalogueCategory[];
    filters: { category: GameCategory | null; search: string | null };
}>();

const search = ref(props.filters.search ?? '');

const activeCategory = computed(
    () => props.categories.find((c) => c.id === props.filters.category) ?? null,
);

const heading = computed(() => activeCategory.value?.name ?? 'All games');

function visit(category: GameCategory | null): void {
    router.get(
        '/games',
        {
            ...(category ? { category } : {}),
            ...(search.value ? { search: search.value } : {}),
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function submitSearch(): void {
    visit(props.filters.category);
}
</script>

<template>
    <Head :title="heading" />

    <PublicShell active="games">
        <section class="px-4 pt-8 pb-10 md:px-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold md:text-4xl">
                        {{ heading }}
                    </h1>
                    <p class="mt-1 text-plum-200">
                        Only games with tested demo access can be played.
                    </p>
                </div>
                <form
                    role="search"
                    class="flex w-full max-w-sm items-end gap-2"
                    @submit.prevent="submitSearch"
                >
                    <div class="grid min-w-0 flex-1 gap-1.5">
                        <label
                            for="game-search"
                            class="text-sm font-semibold text-plum-200"
                            >Search games</label
                        >
                        <input
                            id="game-search"
                            v-model="search"
                            type="search"
                            maxlength="100"
                            placeholder="Game name"
                            class="h-11 w-full min-w-0 rounded-xl border border-plum-400 bg-plum-850 px-4 text-white placeholder:text-plum-200/70 focus:border-gold-500 focus:outline-none"
                        />
                    </div>
                    <button
                        type="submit"
                        aria-label="Search"
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 text-gold-900"
                    >
                        <Search class="size-5" aria-hidden="true" />
                    </button>
                </form>
            </div>

            <div
                role="group"
                aria-label="Filter by category"
                class="-mx-4 mb-8 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:flex-wrap md:px-0"
            >
                <button
                    type="button"
                    :aria-pressed="filters.category === null"
                    class="flex min-h-11 shrink-0 items-center rounded-full border px-5 text-sm font-semibold"
                    :class="
                        filters.category === null
                            ? 'border-gold-500 bg-gold-500 text-gold-900'
                            : 'border-plum-400 bg-plum-850 text-white hover:border-gold-500'
                    "
                    @click="visit(null)"
                >
                    All
                </button>
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    :aria-pressed="filters.category === category.id"
                    class="flex min-h-11 shrink-0 items-center gap-2 rounded-full border py-1 pr-4 pl-2 text-sm font-semibold"
                    :class="
                        filters.category === category.id
                            ? 'border-gold-500 bg-gold-500 text-gold-900'
                            : 'border-plum-400 bg-plum-850 text-white hover:border-gold-500'
                    "
                    @click="visit(category.id)"
                >
                    <CategoryIcon :category="category.id" class="h-7" />
                    {{ category.name }}
                    <span
                        v-if="!category.available"
                        class="rounded-full bg-gold-300 px-1.5 text-[10px] font-bold text-gold-900"
                        >SOON</span
                    >
                </button>
            </div>

            <div
                v-if="activeCategory && !activeCategory.available"
                class="mb-8 flex items-start gap-4 rounded-2xl border border-dashed border-plum-400 bg-plum-900/60 p-6"
            >
                <Clock3
                    class="mt-0.5 size-6 shrink-0 text-gold-300"
                    aria-hidden="true"
                />
                <div>
                    <h2 class="font-bold">
                        {{ activeCategory.name }} isn't available yet
                    </h2>
                    <p class="mt-1 text-sm text-plum-200">
                        This category needs a separately approved provider
                        integration. Nothing here can be played until one has
                        been tested.
                    </p>
                </div>
            </div>

            <p class="mb-4 text-sm text-plum-200">
                {{ games.total }} {{ games.total === 1 ? 'game' : 'games' }}
            </p>

            <div
                v-if="games.data.length > 0"
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-4 xl:grid-cols-5"
            >
                <GameTile
                    v-for="game in games.data"
                    :key="game.slug"
                    :game="game"
                />
            </div>
            <div
                v-else
                class="rounded-2xl border border-dashed border-plum-400 p-12 text-center text-plum-200"
            >
                <p class="font-semibold text-white">No games found</p>
                <p class="mt-1 text-sm">
                    Try another name or
                    <button
                        type="button"
                        class="font-semibold text-gold-300 underline-offset-4 hover:underline"
                        @click="
                            search = '';
                            visit(null);
                        "
                    >
                        show all games</button
                    >.
                </p>
            </div>

            <nav
                v-if="games.prev_page_url || games.next_page_url"
                aria-label="Pagination"
                class="mt-8 flex justify-center gap-3"
            >
                <Link
                    v-if="games.prev_page_url"
                    :href="games.prev_page_url"
                    preserve-scroll
                    class="rounded-xl border border-plum-400 px-5 py-2.5 font-semibold hover:border-gold-500"
                    >Previous</Link
                >
                <Link
                    v-if="games.next_page_url"
                    :href="games.next_page_url"
                    preserve-scroll
                    class="rounded-xl border border-plum-400 px-5 py-2.5 font-semibold hover:border-gold-500"
                    >Next</Link
                >
            </nav>
        </section>
    </PublicShell>
</template>
