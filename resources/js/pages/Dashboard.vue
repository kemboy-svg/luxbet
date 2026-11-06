<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Gamepad2, Heart, History, ShieldCheck, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import GameTile from '@/components/luxbet/GameTile.vue';
import { dashboard } from '@/routes';
import type { CatalogueGame, PlaySessionSummary } from '@/types/luxbet';

const props = defineProps<{
    balance: number;
    allowance: number;
    daily: { date: string; sessions: number; rounds: number }[];
    week: { sessions: number; rounds: number };
    recentGames: CatalogueGame[];
    favourites: CatalogueGame[];
    recentSessions: PlaySessionSummary[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const firstName = computed(() => page.props.auth.user?.name.split(' ')[0]);

const credits = (value: number): string => value.toLocaleString('en');
const signed = (value: number): string =>
    value === 0 ? '0' : (value > 0 ? '+' : '−') + credits(Math.abs(value));

const maxRounds = computed(() =>
    Math.max(1, ...props.daily.map((day) => day.rounds)),
);

const dayLabel = (date: string): string =>
    new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'short',
    });

const formatTime = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString(undefined, {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '—';

const statusLabel: Record<string, string> = {
    created: 'Starting',
    launched: 'In progress',
    completed: 'Ended',
    expired: 'Timed out',
    failed: 'Failed',
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <h1 class="text-2xl font-bold md:text-3xl">
            Welcome back<template v-if="firstName">, {{ firstName }}</template>
        </h1>

        <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr]">
            <section
                aria-labelledby="balance-heading"
                class="flex flex-col justify-between gap-6 rounded-2xl border border-plum-400 bg-linear-to-br from-plum-600 to-plum-850 p-6 md:p-8"
            >
                <div>
                    <h2
                        id="balance-heading"
                        class="text-sm font-medium text-plum-200"
                    >
                        Demo credits
                    </h2>
                    <div class="mt-1 flex flex-wrap items-baseline gap-3">
                        <span
                            class="text-5xl font-extrabold tabular-nums md:text-6xl"
                            >{{ credits(balance) }}</span
                        >
                        <span
                            class="rounded bg-demo px-2 py-0.5 text-xs font-bold tracking-wider text-demo-foreground"
                            >DEMO · NO CASH VALUE</span
                        >
                    </div>
                    <p class="mt-2 text-sm text-plum-200">
                        Starting allowance {{ credits(allowance) }} credits
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/games"
                        class="inline-flex items-center gap-2 rounded-lg bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 px-4 py-2 text-sm font-bold text-gold-900"
                    >
                        <Gamepad2 class="size-4" aria-hidden="true" />
                        Browse games
                    </Link>
                    <Link
                        href="/demo-wallet"
                        class="inline-flex items-center gap-2 rounded-lg border border-plum-400 px-4 py-2 text-sm font-semibold"
                    >
                        <Wallet class="size-4" aria-hidden="true" />
                        Demo wallet
                    </Link>
                </div>
            </section>

            <section
                aria-labelledby="activity-heading"
                class="rounded-2xl border border-border bg-card p-6"
            >
                <div class="flex items-baseline justify-between gap-3">
                    <h2 id="activity-heading" class="font-bold">Last 7 days</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ week.sessions }}
                        {{ week.sessions === 1 ? 'session' : 'sessions' }} ·
                        {{ week.rounds }}
                        {{ week.rounds === 1 ? 'round' : 'rounds' }}
                    </p>
                </div>
                <ul
                    class="mt-6 flex h-36 items-end gap-2"
                    aria-label="Demo rounds played per day"
                >
                    <li
                        v-for="day in daily"
                        :key="day.date"
                        class="flex h-full flex-1 flex-col items-center justify-end gap-1.5"
                    >
                        <span
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            {{ day.rounds || '' }}
                        </span>
                        <span
                            class="w-full max-w-10 rounded-t-md bg-linear-to-t from-gold-600 to-gold-300"
                            :style="{
                                height: `${Math.max(3, (day.rounds / maxRounds) * 100)}%`,
                                opacity: day.rounds ? 1 : 0.25,
                            }"
                            aria-hidden="true"
                        />
                        <span class="text-xs text-muted-foreground">
                            <span class="sr-only"
                                >{{ day.rounds }} rounds on
                            </span>
                            {{ dayLabel(day.date) }}
                        </span>
                    </li>
                </ul>
            </section>
        </div>

        <section aria-labelledby="recent-heading" class="flex flex-col gap-3">
            <h2 id="recent-heading" class="text-xl font-bold">
                Recently played
            </h2>
            <div
                v-if="recentGames.length"
                class="grid grid-cols-2 gap-4 md:grid-cols-4"
            >
                <GameTile
                    v-for="game in recentGames"
                    :key="game.slug"
                    :game="game"
                />
            </div>
            <p
                v-else
                class="rounded-xl border border-dashed border-border p-6 text-sm text-muted-foreground"
            >
                Nothing yet.
                <Link
                    href="/games"
                    class="font-semibold text-gold-400 underline"
                    >Pick a demo game</Link
                >
                to get started.
            </p>
        </section>

        <section aria-labelledby="fav-heading" class="flex flex-col gap-3">
            <div class="flex items-baseline justify-between">
                <h2 id="fav-heading" class="text-xl font-bold">Favourites</h2>
                <Link
                    v-if="favourites.length"
                    href="/favourites"
                    class="text-sm font-semibold text-gold-400"
                    >See all</Link
                >
            </div>
            <div
                v-if="favourites.length"
                class="grid grid-cols-2 gap-4 md:grid-cols-4"
            >
                <GameTile
                    v-for="game in favourites.slice(0, 4)"
                    :key="game.slug"
                    :game="game"
                />
            </div>
            <p
                v-else
                class="flex items-center gap-2 rounded-xl border border-dashed border-border p-6 text-sm text-muted-foreground"
            >
                <Heart class="size-4 shrink-0" aria-hidden="true" />
                Tap the heart on any game page to save it here.
            </p>
        </section>

        <section aria-labelledby="sessions-heading" class="flex flex-col gap-3">
            <div class="flex items-baseline justify-between">
                <h2 id="sessions-heading" class="text-xl font-bold">
                    Recent sessions
                </h2>
                <Link
                    href="/play-history"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-gold-400"
                >
                    <History class="size-4" aria-hidden="true" />
                    Full history
                </Link>
            </div>
            <div
                v-if="recentSessions.length"
                class="overflow-x-auto rounded-xl border border-border"
            >
                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <thead class="bg-card text-left text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Game
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Started
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-medium"
                            >
                                Rounds
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-medium"
                            >
                                Net demo credits
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="session in recentSessions"
                            :key="session.reference"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-semibold">
                                <Link :href="`/games/${session.game.slug}`">{{
                                    session.game.name
                                }}</Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatTime(session.started_at) }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    statusLabel[session.status] ??
                                    session.status
                                }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ session.rounds }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-semibold tabular-nums"
                                :class="
                                    session.net > 0
                                        ? 'text-emerald-400'
                                        : session.net < 0
                                          ? 'text-rose-300'
                                          : ''
                                "
                            >
                                {{ signed(session.net) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p
                v-else
                class="rounded-xl border border-dashed border-border p-6 text-sm text-muted-foreground"
            >
                Your demo sessions will appear here.
            </p>
        </section>

        <p
            class="flex items-start gap-2 rounded-xl border border-demo-band bg-demo-band/40 p-4 text-sm text-demo-band-foreground"
        >
            <ShieldCheck class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            Demo credits are for fun only. They can't be bought, withdrawn or
            exchanged for money or prizes. Set a session reminder in
            <Link href="/settings/responsible-play" class="underline"
                >Responsible play</Link
            >.
        </p>
    </div>
</template>
