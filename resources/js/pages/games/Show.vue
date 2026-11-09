<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Clock3, Heart, Play } from '@lucide/vue';
import { computed } from 'vue';
import GameTile from '@/components/luxbet/GameTile.vue';
import PublicShell from '@/components/luxbet/PublicShell.vue';
import { register } from '@/routes';
import type { CatalogueGame } from '@/types/luxbet';

const props = defineProps<{
    game: CatalogueGame;
    related: CatalogueGame[];
    isFavourite: boolean;
}>();

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth.user));

const playForm = useForm({});

function toggleFavourite(): void {
    const url = `/games/${props.game.slug}/favourite`;
    const options = { preserveScroll: true, preserveState: true } as const;

    if (props.isFavourite) {
        router.delete(url, options);
    } else {
        router.post(url, {}, options);
    }
}

function play(): void {
    playForm.post(`/games/${props.game.slug}/play`, { preserveScroll: true });
}

const providerLabel = computed(() =>
    props.game.provider.is_simulator
        ? 'Local simulator — not a certified casino game'
        : props.game.provider.name,
);
</script>

<template>
    <Head :title="game.name" />

    <PublicShell active="games">
        <section class="px-4 pt-6 pb-10 md:px-8">
            <Link
                :href="`/games?category=${game.category}`"
                class="mb-6 inline-flex items-center gap-2 py-2 text-sm font-semibold text-plum-200 hover:text-gold-300"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ game.category_label }}
            </Link>

            <div class="grid gap-8 lg:grid-cols-[3fr_2fr]">
                <GameTile :game="game" size="lg" />

                <div class="flex flex-col gap-5">
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="rounded bg-demo px-2 py-0.5 text-xs font-bold tracking-wider text-demo-foreground"
                            >DEMO ONLY</span
                        >
                        <span
                            v-if="game.provider.is_simulator"
                            class="rounded border border-plum-400 px-2 py-0.5 text-xs font-bold tracking-wider text-plum-200"
                            >SIMULATOR</span
                        >
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <h1 class="text-4xl font-extrabold md:text-5xl">
                            {{ game.name }}
                        </h1>
                        <button
                            v-if="signedIn"
                            type="button"
                            :aria-pressed="isFavourite"
                            :aria-label="
                                isFavourite
                                    ? 'Remove from favourites'
                                    : 'Add to favourites'
                            "
                            class="flex size-12 shrink-0 items-center justify-center rounded-full border border-plum-400 transition-colors hover:border-gold-500"
                            @click="toggleFavourite"
                        >
                            <Heart
                                class="size-6"
                                :class="
                                    isFavourite
                                        ? 'fill-[#E3223F] text-[#E3223F]'
                                        : 'text-plum-200'
                                "
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                    <p v-if="game.description" class="text-lg text-plum-200">
                        {{ game.description }}
                    </p>

                    <dl
                        class="grid grid-cols-2 gap-4 rounded-2xl border border-plum-500 bg-plum-900/60 p-5 text-sm"
                    >
                        <div>
                            <dt class="text-plum-200">Category</dt>
                            <dd class="mt-0.5 font-semibold">
                                {{ game.category_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-plum-200">Mode</dt>
                            <dd class="mt-0.5 font-semibold">
                                Demo credits only
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-plum-200">Provider</dt>
                            <dd class="mt-0.5 font-semibold">
                                {{ providerLabel }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="game.playable" class="flex flex-col gap-2">
                        <Link
                            v-if="!signedIn"
                            :href="register()"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-8 py-3.5 text-lg font-bold text-gold-900 shadow-[0_6px_24px_rgba(245,179,31,0.45)]"
                        >
                            <Play class="size-5" aria-hidden="true" />
                            Sign up to play free
                        </Link>
                        <template v-else>
                            <button
                                type="button"
                                :disabled="playForm.processing"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-8 py-3.5 text-lg font-bold text-gold-900 shadow-[0_6px_24px_rgba(245,179,31,0.45)] disabled:opacity-60"
                                @click="play"
                            >
                                <Play class="size-5" aria-hidden="true" />
                                {{
                                    playForm.processing
                                        ? 'Starting…'
                                        : 'Play demo'
                                }}
                            </button>
                            <p
                                v-if="page.props.errors.play"
                                role="alert"
                                class="text-sm text-[#F4A79D]"
                            >
                                {{ page.props.errors.play }}
                            </p>
                            <p v-else class="text-sm text-plum-200">
                                Starts a 30-minute demo session. Only demo
                                credits are used.
                            </p>
                            <p
                                v-if="game.simulation"
                                class="text-xs text-plum-200"
                            >
                                Simulated return about
                                {{ game.simulation.rtp }}%<template
                                    v-if="game.simulation.volatility"
                                >
                                    ·
                                    {{ game.simulation.volatility }}
                                    volatility</template
                                >
                                · design figure for this demo, not a certified
                                game
                            </p>
                        </template>
                    </div>
                    <div
                        v-else
                        class="flex items-start gap-3 rounded-2xl border border-dashed border-plum-400 p-5"
                    >
                        <Clock3
                            class="mt-0.5 size-5 shrink-0 text-gold-300"
                            aria-hidden="true"
                        />
                        <p class="text-sm text-plum-200">
                            {{
                                game.status === 'coming_soon'
                                    ? `${game.category_label} need a separately approved provider before they can be played.`
                                    : 'The provider does not offer a demo of this game, so it cannot be played here.'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <section
                v-if="related.length > 0"
                aria-labelledby="related-heading"
                class="mt-12"
            >
                <h2 id="related-heading" class="mb-4 text-2xl font-bold">
                    More {{ game.category_label.toLowerCase() }}
                </h2>
                <div
                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-4"
                >
                    <GameTile v-for="g in related" :key="g.slug" :game="g" />
                </div>
            </section>
        </section>
    </PublicShell>
</template>
