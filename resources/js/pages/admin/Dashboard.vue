<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';

defineProps<{
    roles: string[];
    playerCounts: Record<
        'active' | 'suspended' | 'closed' | 'pending',
        number
    > | null;
    gate: { real_money_enabled: boolean } | null;
}>();

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
            v-if="!playerCounts && !gate"
            class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
        >
            Tools for your role will appear here as they are built.
        </p>
    </div>
</template>
