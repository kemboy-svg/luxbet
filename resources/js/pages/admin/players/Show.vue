<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PlaySessionSummary } from '@/types/luxbet';

type PlayerStatus = 'active' | 'suspended' | 'closed' | 'pending';

const props = defineProps<{
    player: {
        id: string;
        name: string;
        username: string | null;
        email: string;
        status: PlayerStatus;
        email_verified: boolean;
        two_factor: boolean;
        country_code: string | null;
        preferred_locale: string | null;
        joined_at: string | null;
    };
    balance: number | null;
    sessions: PlaySessionSummary[];
    transactions:
        | {
              reference: string;
              type_label: string;
              signed_amount: number;
              balance_after: number;
              description: string;
              created_at: string;
          }[]
        | null;
    audit:
        | {
              id: string;
              action: string;
              reason: string | null;
              created_at: string;
          }[]
        | null;
    can: { change_status: boolean; adjust_wallet: boolean };
    maxAdjustment: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Players', href: '/admin/players' },
            { title: 'Player', href: '#' },
        ],
    },
});

const n = (value: number): string => value.toLocaleString('en');
const signed = (value: number): string =>
    value === 0 ? '0' : (value > 0 ? '+' : '−') + n(Math.abs(value));
const formatTime = (value: string | null): string =>
    value
        ? new Date(value).toLocaleString(undefined, {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '—';

const statusClasses: Record<PlayerStatus, string> = {
    active: 'bg-[#163247] text-[#8fd3f5]',
    suspended: 'bg-[#4a2a12] text-[#f6c08a]',
    closed: 'bg-plum-600 text-plum-200',
    pending: 'bg-plum-600 text-plum-200',
};

const statusForm = useForm<{ status: PlayerStatus; reason: string }>({
    status: props.player.status === 'active' ? 'suspended' : 'active',
    reason: '',
});

function changeStatus(): void {
    statusForm.patch(`/admin/players/${props.player.id}/status`, {
        preserveScroll: true,
        onSuccess: () => statusForm.reset('reason'),
    });
}

const adjustKey = ref(crypto.randomUUID());
const adjustForm = useForm<{
    direction: 'credit' | 'debit';
    amount: number | undefined;
    reason: string;
    idempotency_key: string;
}>({
    direction: 'credit',
    amount: undefined,
    reason: '',
    idempotency_key: adjustKey.value,
});

function adjust(): void {
    adjustForm.idempotency_key = adjustKey.value;
    adjustForm.post(
        `/admin/players/${props.player.id}/demo-wallet/adjustments`,
        {
            preserveScroll: true,
            onSuccess: () => {
                adjustForm.reset('amount', 'reason');
                adjustKey.value = crypto.randomUUID();
            },
        },
    );
}
</script>

<template>
    <Head :title="player.username ?? player.email" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Link
            href="/admin/players"
            class="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" aria-hidden="true" /> All players
        </Link>

        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold">
                {{ player.username ?? player.name }}
            </h1>
            <span
                class="rounded px-2 py-0.5 text-xs font-bold capitalize"
                :class="statusClasses[player.status]"
                >{{ player.status }}</span
            >
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section
                aria-labelledby="account-heading"
                class="rounded-xl border border-border bg-card p-5"
            >
                <h2 id="account-heading" class="font-bold">Account</h2>
                <dl
                    class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm"
                >
                    <dt class="text-muted-foreground">Name</dt>
                    <dd>{{ player.name }}</dd>
                    <dt class="text-muted-foreground">Email</dt>
                    <dd class="break-all">
                        {{ player.email }}
                        <span
                            v-if="!player.email_verified"
                            class="text-xs text-muted-foreground"
                            >(unverified)</span
                        >
                    </dd>
                    <dt class="text-muted-foreground">Country</dt>
                    <dd>{{ player.country_code ?? '—' }}</dd>
                    <dt class="text-muted-foreground">Language</dt>
                    <dd class="uppercase">
                        {{ player.preferred_locale ?? '—' }}
                    </dd>
                    <dt class="text-muted-foreground">Two-factor</dt>
                    <dd>{{ player.two_factor ? 'On' : 'Off' }}</dd>
                    <dt class="text-muted-foreground">Joined</dt>
                    <dd>{{ formatTime(player.joined_at) }}</dd>
                </dl>
            </section>

            <section
                v-if="balance !== null"
                aria-labelledby="wallet-heading"
                class="rounded-xl border border-border bg-card p-5"
            >
                <h2 id="wallet-heading" class="font-bold">Demo wallet</h2>
                <p class="mt-3 text-4xl font-extrabold tabular-nums">
                    {{ n(balance) }}
                </p>
                <p class="text-sm text-muted-foreground">
                    demo credits · no cash value
                </p>

                <form
                    v-if="can.adjust_wallet"
                    class="mt-5 grid gap-3 border-t border-border pt-4"
                    @submit.prevent="adjust"
                >
                    <h3 class="text-sm font-semibold">Adjust demo credits</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="grid gap-1.5">
                            <Label for="adj-direction">Direction</Label>
                            <select
                                id="adj-direction"
                                v-model="adjustForm.direction"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="credit">Add</option>
                                <option value="debit">Remove</option>
                            </select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="adj-amount">Amount</Label>
                            <Input
                                id="adj-amount"
                                v-model.number="adjustForm.amount"
                                type="number"
                                min="1"
                                :max="maxAdjustment"
                                required
                            />
                        </div>
                    </div>
                    <InputError :message="adjustForm.errors.amount" />
                    <div class="grid gap-1.5">
                        <Label for="adj-reason">Reason</Label>
                        <Input
                            id="adj-reason"
                            v-model="adjustForm.reason"
                            required
                            minlength="5"
                            maxlength="200"
                        />
                        <InputError :message="adjustForm.errors.reason" />
                    </div>
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="adjustForm.processing"
                        >Apply adjustment</Button
                    >
                </form>
            </section>

            <section
                v-if="can.change_status"
                aria-labelledby="status-heading"
                class="rounded-xl border border-border bg-card p-5"
            >
                <h2 id="status-heading" class="font-bold">Account status</h2>
                <form class="mt-3 grid gap-3" @submit.prevent="changeStatus">
                    <div class="grid gap-1.5">
                        <Label for="status">New status</Label>
                        <select
                            id="status"
                            v-model="statusForm.status"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="closed">Closed</option>
                        </select>
                        <InputError :message="statusForm.errors.status" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="status-reason">Reason</Label>
                        <Input
                            id="status-reason"
                            v-model="statusForm.reason"
                            required
                            minlength="5"
                            maxlength="500"
                        />
                        <InputError :message="statusForm.errors.reason" />
                    </div>
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="statusForm.processing"
                        >Update status</Button
                    >
                </form>
            </section>
        </div>

        <section aria-labelledby="sessions-heading" class="flex flex-col gap-3">
            <h2 id="sessions-heading" class="text-lg font-bold">
                Recent demo sessions
            </h2>
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full min-w-[720px] border-collapse text-sm">
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
                                Net
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="session in sessions"
                            :key="session.reference"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-semibold">
                                {{ session.game.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatTime(session.started_at) }}
                            </td>
                            <td class="px-4 py-3 capitalize">
                                {{ session.status }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ session.rounds }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ signed(session.net) }}
                            </td>
                        </tr>
                        <tr v-if="sessions.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No sessions yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            v-if="transactions"
            aria-labelledby="ledger-heading"
            class="flex flex-col gap-3"
        >
            <h2 id="ledger-heading" class="text-lg font-bold">
                Recent demo ledger entries
            </h2>
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
                            v-for="tx in transactions"
                            :key="tx.reference"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatTime(tx.created_at) }}
                            </td>
                            <td class="px-4 py-3">{{ tx.type_label }}</td>
                            <td class="px-4 py-3">{{ tx.description }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ signed(tx.signed_amount) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ n(tx.balance_after) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            v-if="audit"
            aria-labelledby="audit-heading"
            class="flex flex-col gap-3"
        >
            <h2 id="audit-heading" class="text-lg font-bold">Audit trail</h2>
            <ul class="divide-y divide-border rounded-xl border border-border">
                <li
                    v-for="entry in audit"
                    :key="entry.id"
                    class="flex flex-wrap justify-between gap-2 px-4 py-3 text-sm"
                >
                    <span>
                        <code class="font-semibold">{{ entry.action }}</code>
                        <span v-if="entry.reason" class="text-muted-foreground">
                            — {{ entry.reason }}</span
                        >
                    </span>
                    <span class="text-muted-foreground">{{
                        formatTime(entry.created_at)
                    }}</span>
                </li>
                <li
                    v-if="audit.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    No audit entries.
                </li>
            </ul>
        </section>
    </div>
</template>
