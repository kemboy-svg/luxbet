<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
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
import LuxBetLogo from '@/components/luxbet/LuxBetLogo.vue';
import { dashboard, login, register } from '@/routes';

/**
 * Shared frame for public pages: header with navigation, the framed
 * content card, trust strip, footer and the mobile tab bar.
 */
const { active = 'home' } = defineProps<{
    active?: 'home' | 'games';
}>();

const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth.user));

const menuOpen = ref(false);

const navLinks = [
    { label: 'Games', href: '/games', key: 'games' },
    { label: 'Categories', href: '/#categories', key: 'categories' },
    { label: 'Responsible Play', href: '/#responsible-play', key: 'rp' },
    { label: 'Help', href: '/#help', key: 'help' },
];

const trustPoints = [
    { icon: ShieldCheck, text: 'Demo credits only — no cash value' },
    { icon: X, text: 'No card details, ever' },
    { icon: Timer, text: 'Session reminders built in' },
    { icon: Headphones, text: 'Help & support' },
];
</script>

<template>
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
                                :aria-current="
                                    active === 'home' ? 'page' : undefined
                                "
                                :class="
                                    active === 'home'
                                        ? 'text-gold-300'
                                        : 'text-white'
                                "
                            >
                                <House class="size-5" aria-hidden="true" />
                            </Link>
                            <Link
                                v-for="link in navLinks"
                                :key="link.label"
                                :href="link.href"
                                :aria-current="
                                    active === link.key ? 'page' : undefined
                                "
                                class="py-2 transition-colors hover:text-gold-300"
                                :class="
                                    active === link.key
                                        ? 'text-gold-300'
                                        : 'text-white'
                                "
                                >{{ link.label }}</Link
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
                        <Link
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            class="block py-3 font-medium text-white"
                            @click="menuOpen = false"
                            >{{ link.label }}</Link
                        >
                        <Link
                            v-if="!signedIn"
                            :href="login()"
                            class="block py-3 font-medium text-gold-300"
                            >Login</Link
                        >
                    </nav>
                </header>

                <slot />

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
                :aria-current="active === 'home' ? 'page' : undefined"
                class="flex min-h-13 flex-col items-center justify-center gap-0.5 text-[11px] font-bold"
                :class="active === 'home' ? 'text-gold-400' : 'text-plum-200'"
            >
                <House class="size-[22px]" aria-hidden="true" />Home
            </Link>
            <Link
                href="/games"
                :aria-current="active === 'games' ? 'page' : undefined"
                class="flex min-h-13 flex-col items-center justify-center gap-0.5 text-[11px] font-semibold"
                :class="active === 'games' ? 'text-gold-400' : 'text-plum-200'"
            >
                <Gamepad2 class="size-[22px]" aria-hidden="true" />Games
            </Link>
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
