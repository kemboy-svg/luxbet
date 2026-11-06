<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';
import { computed } from 'vue';

type Activity = {
    daily: { date: string; sessions: number; rounds: number; staked: number }[];
    sessions: number;
    rounds: number;
    staked: number;
    active_players: number;
    top_games: { name: string; sessions: number; players: number }[];
};

const props = defineProps<{
    roles: string[];
    playerCounts: Record<
        'active' | 'suspended' | 'closed' | 'pending',
        number
    > | null;
    gate: { real_money_enabled: boolean } | null;
    activity: Activity | null;
    webhooks: {
        total: number;
        received: number;
        processed: number;
        rejected: number;
        failed: number;
    } | null;
}>();

const maxSessions = computed(() =>
    Math.max(1, ...(props.activity?.daily.map((d) => d.sessions) ?? [])),
);

const dayLabel = (date: string): string =>
    new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'short',
    });

const n = (value: number): string => value.toLocaleString('en');

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin overview', href: '/admin' }],
    },
});

const roleLabel = (role: string): string =>
    role.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
</script>

<template>
    <Head title="Admin overview" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h1 class="text-2xl font-bold">Admin overview</h1>
            <p class="text-sm text-muted-foreground">
                Signed in as
                <span class="font-semibold text-foreground">{{
                    roles.map(roleLabel).join(', ')
                }}</span>
            </p>
        </div>

        <section
            v-if="playerCounts"
            aria-labelledby="players-heading"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <h2 id="players-heading" class="sr-only">Players by status</h2>
            <div
                v-for="(total, status) in playerCounts"
                :key="status"
                class="rounded-xl border border-border bg-card p-5"
            >
                <div class="text-sm text-muted-foreground capitalize">
                    {{ status }} players
                </div>
                <div class="mt-1 text-3xl font-bold tabular-nums">
                    {{ total.toLocaleString() }}
                </div>
            </div>
        </section>

        <section
            v-if="activity"
            aria-labelledby="activity-heading"
            class="flex flex-col gap-4"
        >
            <h2 id="activity-heading" class="text-lg font-bold">
                Demo play, last 7 days
            </h2>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-border bg-card p-5">
                    <dt class="text-sm text-muted-foreground">Sessions</dt>
                    <dd class="mt-1 text-3xl font-bold tabular-nums">
                        {{ n(activity.sessions) }}
                    </dd>
                </div>
                <div class="rounded-xl border border-border bg-card p-5">
                    <dt class="text-sm text-muted-foreground">
                        Active players
                    </dt>
                    <dd class="mt-1 text-3xl font-bold tabular-nums">
                        {{ n(activity.active_players) }}
                    </dd>
                </div>
                <div class="rounded-xl border border-border bg-card p-5">
                    <dt class="text-sm text-muted-foreground">Rounds</dt>
                    <dd class="mt-1 text-3xl font-bold tabular-nums">
                        {{ n(activity.rounds) }}
                    </dd>
                </div>
                <div class="rounded-xl border border-border bg-card p-5">
                    <dt class="text-sm text-muted-foreground">
                        Demo credits staked
                    </dt>
                    <dd class="mt-1 text-3xl font-bold tabular-nums">
                        {{ n(activity.staked) }}
                    </dd>
                </div>
            </dl>

            <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
                <div class="rounded-xl border border-border bg-card p-5">
                    <h3 class="text-sm font-medium text-muted-foreground">
                        Sessions per day
                    </h3>
                    <ul
                        class="mt-4 flex h-40 items-end gap-2"
                        aria-label="Demo sessions per day"
                    >
                        <li
                            v-for="day in activity.daily"
                            :key="day.date"
                            class="flex h-full flex-1 flex-col items-center justify-end gap-1.5"
                        >
                            <span
                                class="text-xs text-muted-foreground tabular-nums"
                                >{{ day.sessions || '' }}</span
                            >
                            <span
                                class="w-full max-w-10 rounded-t-md bg-linear-to-t from-gold-600 to-gold-300"
                                :style="{
                                    height: `${Math.max(3, (day.sessions / maxSessions) * 100)}%`,
                                    opacity: day.sessions ? 1 : 0.25,
                                }"
                                aria-hidden="true"
                            />
                            <span class="text-xs text-muted-foreground">
                                <span class="sr-only"
                                    >{{ day.sessions }} sessions on
                                </span>
                                {{ dayLabel(day.date) }}
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="rounded-xl border border-border bg-card p-5">
                    <h3 class="text-sm font-medium text-muted-foreground">
                        Top games
                    </h3>
                    <ol
                        v-if="activity.top_games.length"
                        class="mt-3 divide-y divide-border"
                    >
                        <li
                            v-for="(game, i) in activity.top_games"
                            :key="game.name"
                            class="flex items-center justify-between gap-3 py-2.5 text-sm"
                        >
                            <span class="font-semibold"
                                >{{ i + 1 }}. {{ game.name }}</span
                            >
                            <span class="text-muted-foreground tabular-nums"
                                >{{ n(game.sessions) }} sessions ·
                                {{ n(game.players) }} players</span
                            >
                        </li>
                    </ol>
                    <p v-else class="mt-3 text-sm text-muted-foreground">
                        No sessions this week.
                    </p>
                </div>
            </div>
        </section>

        <section
            v-if="webhooks"
            aria-labelledby="webhooks-heading"
            class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-border bg-card p-5"
        >
            <div>
                <h2
                    id="webhooks-heading"
                    class="text-sm font-medium text-muted-foreground"
                >
                    Provider events, last 24 hours
                </h2>
                <p class="mt-1 text-lg font-bold">
                    {{ n(webhooks.total) }} received ·
                    {{ n(webhooks.processed) }} processed ·
                    <span
                        :class="
                            webhooks.rejected + webhooks.failed > 0
                                ? 'text-rose-300'
                                : ''
                        "
                        >{{ n(webhooks.rejected + webhooks.failed) }} rejected
                        or failed</span
                    >
                </p>
            </div>
            <Link
                href="/admin/provider-events"
                class="text-sm font-semibold text-primary underline-offset-4 hover:underline"
                >View events</Link
            >
        </section>

        <section
            v-if="gate"
            aria-labelledby="gate-heading"
            class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-demo-band bg-demo-band/40 p-5"
        >
            <div class="flex items-center gap-3">
                <LockKeyhole
                    class="size-6 text-demo-band-foreground"
                    aria-hidden="true"
                />
                <div>
                    <h2
                        id="gate-heading"
                        class="text-sm font-medium text-demo-band-foreground"
                    >
                        Real-money launch gate
                    </h2>
                    <p
                        class="text-lg font-bold tracking-wide text-demo-band-foreground"
                    >
                        {{ gate.real_money_enabled ? 'ENABLED' : 'DISABLED' }}
                    </p>
                </div>
            </div>
            <Link
                href="/admin/launch-control"
                class="text-sm font-semibold text-primary underline-offset-4 hover:underline"
                >View read-only status</Link
            >
        </section>

        <p
            v-if="!playerCounts && !gate && !activity && !webhooks"
            class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
        >
            Tools for your role will appear here as they are built.
        </p>
    </div>
</template>
