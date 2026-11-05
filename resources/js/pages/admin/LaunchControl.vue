<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';

defineProps<{
    gate: {
        real_money_enabled: boolean;
        blocking_reasons: string[];
        environment_mode: string;
        record_present: boolean;
        record_version: number | null;
        review_due_at: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Launch gate', href: '/admin/launch-control' },
        ],
    },
});
</script>

<template>
    <Head title="Launch gate" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold">Real-money launch gate</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Read-only for every role. There is no control to change it in
                this release.
            </p>
        </div>

        <section
            aria-labelledby="state-heading"
            class="rounded-xl border border-demo-band bg-demo-band/40 p-6"
        >
            <h2
                id="state-heading"
                class="text-sm font-medium text-demo-band-foreground"
            >
                Current state
            </h2>
            <div class="mt-2 flex items-center gap-3">
                <LockKeyhole
                    class="size-8 text-demo-band-foreground"
                    aria-hidden="true"
                />
                <span
                    class="text-3xl font-bold tracking-wide text-demo-band-foreground"
                    >{{
                        gate.real_money_enabled ? 'ENABLED' : 'DISABLED'
                    }}</span
                >
            </div>
        </section>

        <section
            aria-labelledby="record-heading"
            class="rounded-xl border border-border bg-card p-6"
        >
            <h2 id="record-heading" class="mb-4 font-semibold">
                Launch-control record
            </h2>
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted-foreground">Environment mode</dt>
                    <dd class="mt-0.5 font-semibold">
                        {{ gate.environment_mode }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Record</dt>
                    <dd class="mt-0.5 font-semibold">
                        {{
                            gate.record_present
                                ? `Present (version ${gate.record_version})`
                                : 'Missing'
                        }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Review due</dt>
                    <dd class="mt-0.5 font-semibold">
                        {{ gate.review_due_at ?? '—' }}
                    </dd>
                </div>
            </dl>
        </section>

        <section
            aria-labelledby="reasons-heading"
            class="rounded-xl border border-border bg-card p-6"
        >
            <h2 id="reasons-heading" class="mb-1 font-semibold">
                Why it is closed
            </h2>
            <p class="mb-4 text-sm text-muted-foreground">
                Every condition must be met before real money could be
                considered. Any missing item keeps the gate closed.
            </p>
            <ul class="grid gap-2 sm:grid-cols-2">
                <li
                    v-for="reason in gate.blocking_reasons"
                    :key="reason"
                    class="rounded-lg border border-border px-3 py-2 font-mono text-xs"
                >
                    {{ reason }}
                </li>
            </ul>
        </section>
    </div>
</template>
