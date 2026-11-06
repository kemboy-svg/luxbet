<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { RotateCcw, ShieldCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type DemoTransaction = {
    reference: string;
    type: string;
    type_label: string;
    direction: 'credit' | 'debit';
    amount: number;
    signed_amount: number;
    balance_after: number;
    description: string;
    created_at: string;
};

type PageLink = { url: string | null; label: string; active: boolean };

const props = defineProps<{
    wallet: { id: string; balance: number; status: string };
    transactions: {
        data: DemoTransaction[];
        links: { prev: string | null; next: string | null };
        meta: {
            current_page: number;
            last_page: number;
            total: number;
            links: PageLink[];
        };
    };
    filters: { type: string | null };
    types: { value: string; label: string }[];
    allowance: number;
    resetAvailable: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Demo wallet', href: '/demo-wallet' }],
    },
});

const resetForm = useForm({});
const page = usePage();

const credits = (value: number): string => value.toLocaleString('en');

const signed = (value: number): string =>
    (value > 0 ? '+' : '−') + credits(Math.abs(value));

const formatTime = (value: string): string =>
    new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

function filterByType(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    router.get('/demo-wallet', value ? { type: value } : {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function reset(): void {
    resetForm.post('/demo-wallet/reset', { preserveScroll: true });
}
</script>

<template>
    <Head title="Demo wallet" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <h1 class="text-2xl font-bold md:text-3xl">Demo wallet</h1>

        <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
            <section
                aria-labelledby="balance-heading"
                class="flex flex-wrap items-end justify-between gap-6 rounded-2xl border border-plum-400 bg-linear-to-br from-plum-600 to-plum-850 p-6 md:p-8"
            >
                <div>
                    <h2
                        id="balance-heading"
                        class="text-sm font-medium text-plum-200"
                    >
                        Current balance
                    </h2>
                    <div class="mt-1 flex flex-wrap items-baseline gap-3">
                        <span
                            class="text-5xl font-extrabold tabular-nums md:text-6xl"
                            >{{ credits(wallet.balance) }}</span
                        >
                        <span class="text-lg text-plum-200">credits</span>
                        <span
                            class="rounded bg-demo px-2 py-0.5 text-xs font-bold tracking-wider text-demo-foreground"
                            >DEMO · NO CASH VALUE</span
                        >
                    </div>
                    <p class="mt-3 text-sm text-plum-200">
                        Starting allowance {{ credits(allowance) }} credits
                    </p>
                </div>

                <div class="flex flex-col items-start gap-1.5">
                    <Button
                        type="button"
                        :disabled="!resetAvailable || resetForm.processing"
                        class="bg-linear-to-b from-gold-200 via-gold-500 to-gold-600 font-bold text-gold-900"
                        @click="reset"
                    >
                        <RotateCcw class="size-4" aria-hidden="true" />
                        Reset to {{ credits(allowance) }} credits
                    </Button>
                    <span class="text-xs text-plum-200">{{
                        resetAvailable
                            ? 'Available once a day'
                            : 'Available once a day when you are below the starting allowance'
                    }}</span>
                    <InputError :message="page.props.errors.reset" />
                </div>
            </section>

            <section
                aria-labelledby="no-money-heading"
                class="rounded-2xl border border-demo-band bg-demo-band/50 p-6"
            >
                <ShieldCheck
                    class="mb-3 size-6 text-demo-band-foreground"
                    aria-hidden="true"
                />
                <h2
                    id="no-money-heading"
                    class="mb-2 font-bold text-demo-band-foreground"
                >
                    Looking for deposits or withdrawals?
                </h2>
                <p class="text-sm text-demo-band-foreground/90">
                    There aren't any. LuxBet is a demo: credits can't be bought,
                    transferred, withdrawn or swapped for money or prizes. We
                    never ask for card or bank details.
                </p>
            </section>
        </div>

        <section aria-labelledby="history-heading" class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 id="history-heading" class="text-xl font-bold">
                    Demo transactions
                </h2>
                <div class="grid gap-1.5">
                    <Label for="type-filter">Type</Label>
                    <select
                        id="type-filter"
                        :value="filters.type ?? ''"
                        class="h-10 min-w-48 rounded-md border border-input bg-background px-3 text-sm"
                        @change="filterByType"
                    >
                        <option value="">All types</option>
                        <option
                            v-for="type in types"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full min-w-[720px] border-collapse text-sm">
                    <thead class="bg-card text-left text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Time
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Type
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
                            v-for="tx in props.transactions.data"
                            :key="tx.reference"
                            class="border-t border-border"
                        >
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatTime(tx.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded border border-plum-400 bg-plum-700 px-2 py-0.5 text-xs font-bold whitespace-nowrap"
                                    >{{ tx.type_label }}</span
                                >
                            </td>
                            <td class="px-4 py-3">{{ tx.description }}</td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ tx.reference }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-semibold whitespace-nowrap tabular-nums"
                                :class="
                                    tx.direction === 'credit'
                                        ? 'text-[#8fd3f5]'
                                        : 'text-[#f6c08a]'
                                "
                            >
                                {{ signed(tx.signed_amount) }}
                            </td>
                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{ credits(tx.balance_after) }}
                            </td>
                        </tr>
                        <tr v-if="props.transactions.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No demo transactions yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground"
            >
                <span>All amounts are demo credits with no cash value.</span>
                <div class="flex gap-2">
                    <Link
                        v-if="props.transactions.links.prev"
                        :href="props.transactions.links.prev"
                        preserve-scroll
                        class="rounded-md border border-border px-4 py-2 font-semibold text-foreground"
                        >Newer</Link
                    >
                    <Link
                        v-if="props.transactions.links.next"
                        :href="props.transactions.links.next"
                        preserve-scroll
                        class="rounded-md border border-border px-4 py-2 font-semibold text-foreground"
                        >Older</Link
                    >
                </div>
            </div>
        </section>
    </div>
</template>
