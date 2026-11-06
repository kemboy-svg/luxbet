<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Row = {
    reference: string;
    player: { id: string; email: string; username: string | null };
    type: string;
    type_label: string;
    source: string;
    signed_amount: number;
    balance_after: number;
    description: string;
    created_at: string;
};

const props = defineProps<{
    transactions: {
        data: Row[];
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { type: string | null; search: string };
    types: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Demo ledger', href: '/admin/demo-transactions' },
        ],
    },
});

const search = ref(props.filters.search);
const type = ref(props.filters.type ?? '');

function apply(): void {
    const query: Record<string, string> = {};
    if (search.value) query.search = search.value;
    if (type.value) query.type = type.value;
    router.get('/admin/demo-transactions', query, {
        preserveState: true,
        replace: true,
    });
}

const n = (value: number): string => value.toLocaleString('en');
const signed = (value: number): string =>
    (value > 0 ? '+' : '−') + n(Math.abs(value));
const formatTime = (value: string): string =>
    new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
</script>

<template>
    <Head title="Demo ledger" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold">Demo ledger</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Every demo-credit movement across all players. Read-only; demo
                credits have no cash value.
            </p>
        </div>

        <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
            <div class="grid gap-1.5">
                <Label for="search">Reference or player email</Label>
                <Input id="search" v-model="search" class="w-72" />
            </div>
            <div class="grid gap-1.5">
                <Label for="type">Type</Label>
                <select
                    id="type"
                    v-model="type"
                    class="h-9 min-w-44 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">All types</option>
                    <option v-for="t in types" :key="t.value" :value="t.value">
                        {{ t.label }}
                    </option>
                </select>
            </div>
            <Button type="submit" variant="secondary">Filter</Button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[960px] border-collapse text-sm">
                <thead class="bg-card text-left text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Time</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Player
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Type</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Source
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Detail
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Reference
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Credits
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Balance after
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="tx in transactions.data"
                        :key="tx.reference"
                        class="border-t border-border"
                    >
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatTime(tx.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                :href="`/admin/players/${tx.player.id}`"
                                class="font-semibold underline-offset-4 hover:underline"
                                >{{
                                    tx.player.username ?? tx.player.email
                                }}</Link
                            >
                        </td>
                        <td class="px-4 py-3">{{ tx.type_label }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ tx.source }}
                        </td>
                        <td class="px-4 py-3">{{ tx.description }}</td>
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ tx.reference }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ signed(tx.signed_amount) }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ n(tx.balance_after) }}
                        </td>
                    </tr>
                    <tr v-if="transactions.data.length === 0">
                        <td
                            colspan="8"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No ledger entries match.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="transactions.links" />
    </div>
</template>
