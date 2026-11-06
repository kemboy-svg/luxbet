<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type EventRow = {
    id: string;
    provider: string;
    event_id: string;
    event_type: string;
    session_reference: string | null;
    round_reference: string | null;
    amount: number | null;
    status: 'received' | 'processed' | 'rejected' | 'failed';
    failure_reason: string | null;
    received_at: string;
    processed_at: string | null;
};

const props = defineProps<{
    events: {
        data: EventRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { status: string | null; provider: string | null };
    statuses: string[];
    providers: { id: string; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Provider events', href: '/admin/provider-events' },
        ],
    },
});

const status = ref(props.filters.status ?? '');
const provider = ref(props.filters.provider ?? '');

function apply(): void {
    const query: Record<string, string> = {};
    if (status.value) query.status = status.value;
    if (provider.value) query.provider = provider.value;
    router.get('/admin/provider-events', query, {
        preserveState: true,
        replace: true,
    });
}

const statusClasses: Record<EventRow['status'], string> = {
    received: 'bg-plum-600 text-plum-200',
    processed: 'bg-[#163247] text-[#8fd3f5]',
    rejected: 'bg-[#4a2a12] text-[#f6c08a]',
    failed: 'bg-[#4a1220] text-[#f5a3b4]',
};

const formatTime = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString(undefined, {
              dateStyle: 'medium',
              timeStyle: 'medium',
          })
        : '—';
</script>

<template>
    <Head title="Provider events" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold">Provider events</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Signed events received from game providers. Payloads are not
                stored, only their hash and the fields below.
            </p>
        </div>

        <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
            <div class="grid gap-1.5">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    v-model="status"
                    class="h-9 min-w-40 rounded-md border border-input bg-background px-3 text-sm capitalize"
                >
                    <option value="">All statuses</option>
                    <option v-for="s in statuses" :key="s" :value="s">
                        {{ s }}
                    </option>
                </select>
            </div>
            <div class="grid gap-1.5">
                <Label for="provider">Provider</Label>
                <select
                    id="provider"
                    v-model="provider"
                    class="h-9 min-w-48 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">All providers</option>
                    <option v-for="p in providers" :key="p.id" :value="p.id">
                        {{ p.name }}
                    </option>
                </select>
            </div>
            <Button type="submit" variant="secondary">Filter</Button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[1000px] border-collapse text-sm">
                <thead class="bg-card text-left text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Received
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Provider
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Type</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Event ID
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Session / round
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Amount
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="event in events.data"
                        :key="event.id"
                        class="border-t border-border align-top"
                    >
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatTime(event.received_at) }}
                        </td>
                        <td class="px-4 py-3">{{ event.provider }}</td>
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ event.event_type }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs break-all">
                            {{ event.event_id }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs break-all">
                            {{ event.session_reference ?? '—' }}
                            <template v-if="event.round_reference">
                                / {{ event.round_reference }}</template
                            >
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ event.amount?.toLocaleString('en') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded px-2 py-0.5 text-xs font-bold capitalize"
                                :class="statusClasses[event.status]"
                                >{{ event.status }}</span
                            >
                            <p
                                v-if="event.failure_reason"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ event.failure_reason }}
                            </p>
                        </td>
                    </tr>
                    <tr v-if="events.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No events match.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="events.links" />
    </div>
</template>
