<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, CircleX, RefreshCw } from '@lucide/vue';
import { Button } from '@/components/ui/button';

type Check = {
    name: string;
    description: string;
    status: 'ok' | 'warn' | 'fail';
    detail: string;
};

defineProps<{
    checks: Check[];
    environment: { mode: string; app_env: string; debug: boolean };
    checked_at: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'System health', href: '/admin/system-health' }],
    },
});

const icons = { ok: CircleCheck, warn: CircleAlert, fail: CircleX };
const colours: Record<Check['status'], string> = {
    ok: 'text-emerald-400',
    warn: 'text-amber-300',
    fail: 'text-rose-400',
};
const labels: Record<Check['status'], string> = {
    ok: 'OK',
    warn: 'Warning',
    fail: 'Failing',
};

function refresh(): void {
    router.reload();
}
</script>

<template>
    <Head title="System health" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">System health</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Checked {{ new Date(checked_at).toLocaleTimeString() }} ·
                    mode <strong>{{ environment.mode }}</strong> · env
                    <strong>{{ environment.app_env }}</strong>
                    <template v-if="environment.debug">
                        · <span class="text-amber-300">debug on</span></template
                    >
                </p>
            </div>
            <Button type="button" variant="secondary" @click="refresh">
                <RefreshCw class="size-4" aria-hidden="true" /> Re-run checks
            </Button>
        </div>

        <ul class="divide-y divide-border rounded-xl border border-border">
            <li
                v-for="check in checks"
                :key="check.name"
                class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
            >
                <div class="flex items-start gap-3">
                    <component
                        :is="icons[check.status]"
                        class="mt-0.5 size-5 shrink-0"
                        :class="colours[check.status]"
                        aria-hidden="true"
                    />
                    <div>
                        <p class="font-semibold">{{ check.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ check.description }}
                        </p>
                    </div>
                </div>
                <p class="text-sm">
                    <span class="font-bold" :class="colours[check.status]">{{
                        labels[check.status]
                    }}</span>
                    · {{ check.detail }}
                </p>
            </li>
        </ul>
    </div>
</template>
