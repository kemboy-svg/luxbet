<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard, login, register } from '@/routes';

// Placeholder landing page for the foundation milestone.
// The full home page from the Glam v2 designs is built in Milestone 6.
</script>

<template>
    <Head title="Demo casino" />
    <div
        class="flex min-h-[calc(100vh-2.5rem)] flex-col bg-plum-900 text-white"
    >
        <header
            class="flex items-center justify-between border-b border-plum-500 px-6 py-4"
        >
            <div class="flex items-center gap-2">
                <AppLogoIcon class="h-8 w-10 text-gold-500" />
                <span class="flex flex-col leading-none">
                    <span class="text-2xl font-extrabold text-gold-400"
                        >LuxBet</span
                    >
                    <span
                        class="mt-1 text-[10px] font-semibold tracking-[0.45em] text-gold-100"
                        >CASINO</span
                    >
                </span>
            </div>
            <nav class="flex items-center gap-3 text-sm font-semibold">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-lg border border-gold-600 px-4 py-2 text-white hover:bg-plum-600"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-lg border border-gold-600 px-4 py-2 text-white hover:bg-plum-600"
                    >
                        Login
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-lg bg-gold-500 px-4 py-2 font-bold text-gold-900 hover:bg-gold-400"
                    >
                        Sign Up
                    </Link>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-3xl flex-1 flex-col items-start justify-center gap-6 px-6 py-16"
        >
            <span
                class="rounded-md bg-demo px-2.5 py-1 text-xs font-bold tracking-[0.14em] text-demo-foreground"
                >FREE DEMO · NO DEPOSITS</span
            >
            <h1 class="text-5xl leading-none font-extrabold sm:text-7xl">
                Play Big.<br />
                <span class="text-gold-400">Risk Nothing.</span>
            </h1>
            <p class="max-w-xl text-lg text-plum-200">
                A premium casino experience from Mauritius, played with free
                demo credits. Nothing to deposit, nothing to withdraw.
            </p>
            <Link
                :href="register()"
                class="rounded-full bg-gold-500 px-8 py-3.5 text-lg font-bold text-gold-900 shadow-lg shadow-gold-500/30 hover:bg-gold-400"
            >
                Play Free Demo
            </Link>
        </main>

        <footer class="px-6 py-6 text-center text-xs text-plum-200">
            LuxBet is a non-cash demo platform. It does not accept real-money
            players, process payments or hold a gambling licence. 18+ only.
        </footer>
    </div>
</template>
