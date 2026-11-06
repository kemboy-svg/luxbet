<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { PlaySessionSummary } from '@/types/luxbet';

type PageLink = { url: string | null; label: string; active: boolean };

defineProps<{
    sessions: {
        data: PlaySessionSummary[];
        current_page: number;
        last_page: number;
        total: number;
        links: PageLink[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Play history', href: '/play-history' }],
    },
});

const credits = (value: number): string => value.toLocaleString('en');
const signed = (value: number): string =>
    value === 0 ? '0' : (value > 0 ? '+' : '−') + credits(Math.abs(value));

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

const pageLabel = (label: string): string =>
    label.replace('&laquo;', '‹').replace('&raquo;', '›');
</script>

<template>
    <Head title="Play history" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold md:text-3xl">Play history</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ sessions.total }} demo
                {{ sessions.total === 1 ? 'session' : 'sessions' }}. All amounts
                are demo credits with no cash value.
            </p>
        </div>

        <div
            v-if="sessions.data.length"
            class="overflow-x-auto rounded-xl border border-border"
        >
            <table class="w-full min-w-[820px] border-collapse text-sm">
                <thead class="bg-card text-left text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Game</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Started
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Ended</th>
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
                            Staked
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Won
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Net
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="session in sessions.data"
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
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatTime(session.ended_at) }}
                        </td>
                        <td class="px-4 py-3">
                            {{ statusLabel[session.status] ?? session.status }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ session.rounds }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ credits(session.staked) }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ credits(session.won) }}
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
            No demo sessions yet.
            <Link href="/games" class="font-semibold text-gold-400 underline"
                >Pick a game</Link
            >
            to start one.
        </p>

        <nav
            v-if="sessions.last_page > 1"
            aria-label="Pagination"
            class="flex flex-wrap gap-1"
        >
            <template v-for="link in sessions.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    class="min-w-9 rounded-md border border-border px-3 py-1.5 text-center text-sm"
                    :class="
                        link.active ? 'bg-primary text-primary-foreground' : ''
                    "
                    >{{ pageLabel(link.label) }}</Link
                >
                <span
                    v-else
                    class="min-w-9 rounded-md px-3 py-1.5 text-center text-sm text-muted-foreground"
                    >{{ pageLabel(link.label) }}</span
                >
            </template>
        </nav>
    </div>
</template>
