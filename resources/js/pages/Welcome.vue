<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Gamepad2,
    Headphones,
    Heart,
    House,
    Menu,
    ShieldCheck,
    Timer,
    UserRound,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryIcon from '@/components/luxbet/CategoryIcon.vue';
import GameTile from '@/components/luxbet/GameTile.vue';
import HeroScene from '@/components/luxbet/HeroScene.vue';
import LuxBetLogo from '@/components/luxbet/LuxBetLogo.vue';
import { showcaseCategories, showcaseGames } from '@/lib/showcase';
import { dashboard, login, register } from '@/routes';

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth.user));

// Until the game catalogue exists (Milestone 4), playable tiles lead to
// sign-up for guests and to the dashboard for signed-in players.
const playHref = computed(() => (signedIn.value ? dashboard() : register()));

const menuOpen = ref(false);

const navLinks = [
    { label: 'Games', href: '#games' },
    { label: 'Categories', href: '#categories' },
    { label: 'Responsible Play', href: '#responsible-play' },
    { label: 'Help', href: '#help' },
];

const trustPoints = [
    { icon: ShieldCheck, text: 'Demo credits only — no cash value' },
    { icon: X, text: 'No card details, ever' },
    { icon: Timer, text: 'Session reminders built in' },
    { icon: Headphones, text: 'Help & support' },
];
</script>

<template>
    <Head title="Play Big. Risk Nothing." />

    <div class="min-h-svh bg-plum-900 pb-24 text-white md:pb-0">
        <div class="mx-auto max-w-[1360px] md:px-6 md:pt-5 md:pb-10">
            <div
                class="overflow-hidden border-plum-400 bg-plum-850 md:rounded-[22px] md:border md:shadow-[0_30px_80px_rgba(0,0,0,0.5)]"
            >
                <!-- Header -->
                <header
                    class="relative z-20 border-b border-plum-500 bg-plum-900"
                >
                    <div
                        class="flex items-center justify-between gap-4 px-4 py-3 md:px-8"
                    >
                        <Link href="/" aria-label="LuxBet home">
                            <LuxBetLogo size="md" />
                        </Link>

                        <nav
                            aria-label="Main"
                            class="hidden items-center gap-8 text-[15px] font-medium lg:flex"
                        >
                            <Link
                                href="/"
                                aria-label="Home"
                                aria-current="page"
                                class="text-gold-300"
                            >
                                <House class="size-5" aria-hidden="true" />
                            </Link>
                            <a
                                v-for="link in navLinks"
                                :key="link.label"
                                :href="link.href"
                                class="py-2 text-white transition-colors hover:text-gold-300"
                                >{{ link.label }}</a
                            >
                        </nav>

                        <div class="flex items-center gap-2">
                            <Link
                                v-if="signedIn"
                                :href="dashboard()"
                                class="rounded-[10px] bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-5 py-2.5 text-sm font-bold text-gold-900 shadow-[0_4px_16px_rgba(245,179,31,0.35)]"
                            >
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="login()"
                                    class="hidden rounded-[10px] border-[1.5px] border-gold-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-plum-600 sm:inline-block"
                                >
                                    Login
                                </Link>
                                <Link
                                    :href="register()"
                                    class="rounded-[10px] bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-5 py-2.5 text-sm font-bold text-gold-900 shadow-[0_4px_16px_rgba(245,179,31,0.35)]"
                                >
                                    Sign Up
                                </Link>
                            </template>
                            <button
                                type="button"
                                class="flex size-11 items-center justify-center rounded-lg text-white lg:hidden"
                                :aria-expanded="menuOpen"
                                aria-controls="mobile-menu"
                                :aria-label="
                                    menuOpen ? 'Close menu' : 'Open menu'
                                "
                                @click="menuOpen = !menuOpen"
                            >
                                <X
                                    v-if="menuOpen"
                                    class="size-6"
                                    aria-hidden="true"
                                />
                                <Menu
                                    v-else
                                    class="size-6"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                    </div>

                    <nav
                        v-show="menuOpen"
                        id="mobile-menu"
                        aria-label="Mobile"
                        class="border-t border-plum-500 px-4 py-2 lg:hidden"
                    >
                        <a
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            class="block py-3 font-medium text-white"
                            @click="menuOpen = false"
                            >{{ link.label }}</a
                        >
                        <Link
                            v-if="!signedIn"
                            :href="login()"
                            class="block py-3 font-medium text-gold-300"
                            >Login</Link
                        >
                    </nav>
                </header>

                <!-- Hero -->
                <section
                    class="relative h-[460px] overflow-hidden md:h-[540px]"
                >
                    <HeroScene />
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
                            A premium casino experience from Mauritius — played
                            with free demo credits.
                        </p>
                        <div class="flex flex-wrap items-center gap-4">
                            <Link
                                :href="playHref"
                                class="rounded-full bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-9 py-3.5 text-lg font-bold text-gold-900 shadow-[0_6px_24px_rgba(245,179,31,0.45),inset_0_1px_0_rgba(255,255,255,0.6)] transition-transform hover:-translate-y-0.5"
                            >
                                {{
                                    signedIn
                                        ? 'Go to dashboard'
                                        : 'Play Free Demo'
                                }}
                            </Link>
                            <a
                                href="#games"
                                class="px-2 py-3 font-semibold text-white hover:text-gold-300"
                                >Browse games →</a
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
                        <li
                            v-for="category in showcaseCategories"
                            :key="category.id"
                        >
                            <a
                                href="#games"
                                class="relative flex flex-col items-center gap-2 rounded-2xl border-[1.5px] border-[#7A5520] bg-linear-to-b from-[#2A1546] to-plum-850 px-3 pt-4 pb-3.5 shadow-[0_10px_30px_rgba(0,0,0,0.45)] transition-colors hover:border-gold-500"
                            >
                                <span
                                    v-if="!category.available"
                                    class="absolute top-2 right-2 rounded-full bg-gold-300 px-2 py-px text-[10px] font-bold tracking-wide text-gold-900"
                                    >SOON</span
                                >
                                <CategoryIcon
                                    :category="category.id"
                                    class="h-14 w-16"
                                />
                                <span class="text-base font-semibold">{{
                                    category.name
                                }}</span>
                            </a>
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
                            :href="playHref"
                            class="py-2 text-[15px] font-medium text-white hover:text-gold-300"
                            >View All →</Link
                        >
                    </div>
                    <div
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-5"
                    >
                        <GameTile
                            v-for="game in showcaseGames"
                            :key="game.title"
                            :game="game"
                            :href="playHref"
                        />
                    </div>
                </section>

                <!-- Trust strip -->
                <section
                    aria-label="Why LuxBet"
                    class="flex flex-wrap justify-around gap-x-6 gap-y-4 border-t border-plum-500 bg-plum-900 px-4 py-5 md:px-8"
                >
                    <div
                        v-for="point in trustPoints"
                        :key="point.text"
                        class="flex items-center gap-3 text-sm font-medium"
                    >
                        <component
                            :is="point.icon"
                            class="size-6 text-gold-400"
                            aria-hidden="true"
                        />
                        {{ point.text }}
                    </div>
                </section>
            </div>

            <!-- Footer -->
            <footer
                class="grid gap-6 px-5 py-8 text-sm text-plum-200 md:grid-cols-2 md:px-2"
            >
                <section
                    id="responsible-play"
                    aria-labelledby="rp-heading"
                    class="scroll-mt-24"
                >
                    <h2 id="rp-heading" class="mb-1 font-semibold text-white">
                        Responsible play
                    </h2>
                    <p>
                        LuxBet is for entertainment only. Set a session reminder
                        in your account settings, and take regular breaks.
                    </p>
                </section>
                <section
                    id="help"
                    aria-labelledby="help-heading"
                    class="scroll-mt-24"
                >
                    <h2 id="help-heading" class="mb-1 font-semibold text-white">
                        Help
                    </h2>
                    <p>
                        LuxBet is a non-cash demo platform. It does not accept
                        real-money players, process payments or hold a gambling
                        licence. 18+ only.
                    </p>
                </section>
            </footer>
        </div>

        <!-- Mobile tab bar -->
        <nav
            aria-label="Primary"
            class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-plum-500 bg-plum-950/95 px-1 pt-1.5 pb-[max(env(safe-area-inset-bottom),0.75rem)] backdrop-blur md:hidden"
        >
            <Link
                href="/"
                aria-current="page"
                class="flex min-h-13 flex-col items-center justify-center gap-0.5 text-[11px] font-bold text-gold-400"
            >
                <House class="size-[22px]" aria-hidden="true" />Home
            </Link>
            <a
                href="#games"
                class="flex min-h-13 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold text-plum-200"
            >
                <Gamepad2 class="size-[22px]" aria-hidden="true" />Games
            </a>
            <Link
                :href="signedIn ? dashboard() : login()"
                class="flex min-h-13 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold text-plum-200"
            >
                <Heart class="size-[22px]" aria-hidden="true" />Favourites
            </Link>
            <Link
                :href="signedIn ? dashboard() : login()"
                class="flex min-h-13 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold text-plum-200"
            >
                <UserRound class="size-[22px]" aria-hidden="true" />Account
            </Link>
        </nav>
    </div>
</template>
