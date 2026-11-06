<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LogRow = {
    id: string;
    action: string;
    actor: string;
    subject_type: string | null;
    subject_id: string | null;
    reason: string | null;
    metadata: Record<string, unknown>;
    ip_address: string | null;
    created_at: string;
};

const props = defineProps<{
    logs: {
        data: LogRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { action: string | null; actor: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Audit log', href: '/admin/audit-log' }],
    },
});

const action = ref(props.filters.action ?? '');
const actor = ref(props.filters.actor);

function apply(): void {
    const query: Record<string, string> = {};
    if (action.value) query.action = action.value;
    if (actor.value) query.actor = actor.value;
    router.get('/admin/audit-log', query, {
        preserveState: true,
        replace: true,
    });
}

const formatTime = (value: string): string =>
    new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'medium',
    });

const hasMetadata = (row: LogRow): boolean =>
    Object.keys(row.metadata ?? {}).length > 0;
</script>

<template>
    <Head title="Audit log" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold">Audit log</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Append-only record of sensitive actions. Secrets were redacted
                when each entry was written.
            </p>
        </div>

        <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
            <div class="grid gap-1.5">
                <Label for="action">Action starts with</Label>
                <Input
                    id="action"
                    v-model="action"
                    placeholder="e.g. staff. or player."
                    class="w-60"
                />
            </div>
            <div class="grid gap-1.5">
                <Label for="actor">Actor email</Label>
                <Input id="actor" v-model="actor" type="email" class="w-64" />
            </div>
            <Button type="submit" variant="secondary">Filter</Button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[960px] border-collapse text-sm">
                <thead class="bg-card text-left text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Time</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Action
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Actor</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Subject
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Reason and details
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="log in logs.data"
                        :key="log.id"
                        class="border-t border-border align-top"
                    >
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatTime(log.created_at) }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs font-semibold">
                            {{ log.action }}
                        </td>
                        <td class="px-4 py-3 break-all">
                            {{ log.actor }}
                            <span
                                v-if="log.ip_address"
                                class="block text-xs text-muted-foreground"
                                >{{ log.ip_address }}</span
                            >
                        </td>
                        <td class="px-4 py-3 font-mono text-xs break-all">
                            <template v-if="log.subject_type"
                                >{{ log.subject_type }}<br />{{
                                    log.subject_id
                                }}</template
                            >
                            <template v-else>—</template>
                        </td>
                        <td class="px-4 py-3">
                            <p v-if="log.reason">{{ log.reason }}</p>
                            <details v-if="hasMetadata(log)" class="mt-1">
                                <summary
                                    class="cursor-pointer text-xs text-muted-foreground"
                                >
                                    Details
                                </summary>
                                <pre
                                    class="mt-1 max-w-md overflow-x-auto rounded bg-muted p-2 text-xs"
                                    >{{
                                        JSON.stringify(log.metadata, null, 2)
                                    }}</pre>
                            </details>
                        </td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No entries match.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="logs.links" />
    </div>
</template>
