<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Minus, Plus, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import HeroScene from '@/components/luxbet/HeroScene.vue';
import LuxBetLogo from '@/components/luxbet/LuxBetLogo.vue';
import SlotSymbol from '@/components/luxbet/SlotSymbol.vue';
import type { CatalogueGame } from '@/types/luxbet';

type RoundDisplay =
    | {
          kind: 'slots';
          reels: string[][];
          winning_symbol: string | null;
          match_count: number;
      }
    | {
          kind: 'blackjack' | 'baccarat';
          player_cards: string[];
          dealer_cards: string[];
      };

type Round = {
    round_id: string;
    stake: number;
    payout: number;
    result: 'win' | 'lose' | 'push';
    display: RoundDisplay;
    balance: number;
};

const props = defineProps<{
    session: {
        id: string;
        status: string;
        open: boolean;
        expires_at: string;
    };
    game: CatalogueGame;
    balance: number;
    stakes: number[];
    returnUrl: string;
}>();

const isSlots = computed(() => props.game.category === 'slots');
const balance = ref(props.balance);
const stakeIndex = ref(Math.min(2, props.stakes.length - 1));
const stake = computed(() => props.stakes[stakeIndex.value] ?? props.stakes[0]);
const playing = ref(false);
const error = ref<string | null>(null);
const lastRound = ref<Round | null>(null);
const sessionOpen = ref(props.session.open);

const initialReels = [
    ['seven', 'heart', 'cherry'],
    ['bell', 'diamond', 'heart'],
    ['cherry', 'seven', 'diamond'],
    ['diamond', 'cherry', 'bar'],
    ['bar', 'crown', 'heart'],
];

const reels = computed(() =>
    lastRound.value?.display.kind === 'slots'
        ? lastRound.value.display.reels
        : initialReels,
);

const tableCards = computed(() =>
    lastRound.value && lastRound.value.display.kind !== 'slots'
        ? lastRound.value.display
        : null,
);

const credits = (value: number): string => value.toLocaleString('en');

const resultText = computed(() => {
    const round = lastRound.value;
    if (!round) {
        return null;
    }
    if (round.result === 'win') {
        return `You won ${credits(round.payout)} demo credits`;
    }
    if (round.result === 'push') {
        return 'Push — stake returned';
    }
    return 'No win this time';
});

const minutesLeft = ref(0);
function updateMinutes(): void {
    minutesLeft.value = Math.max(
        0,
        Math.round(
            (new Date(props.session.expires_at).getTime() - Date.now()) / 60000,
        ),
    );
    if (minutesLeft.value === 0) {
        sessionOpen.value = false;
    }
}
updateMinutes();
const timer = window.setInterval(updateMinutes, 15000);
onBeforeUnmount(() => window.clearInterval(timer));

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function play(): Promise<void> {
    if (playing.value || !sessionOpen.value) {
        return;
    }

    playing.value = true;
    error.value = null;

    try {
        const response = await fetch(
            `/api/v1/game-sessions/${props.session.id}/simulator-rounds`,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ stake: stake.value }),
            },
        );
        const body = await response.json();

        if (!response.ok) {
            error.value =
                body?.error?.message ?? 'This round could not be played.';
            if (body?.error?.message?.includes('ended')) {
                sessionOpen.value = false;
            }
            return;
        }

        lastRound.value = body.data as Round;
        balance.value = body.data.balance;
    } catch {
        error.value = 'Connection problem. Please try again.';
    } finally {
        playing.value = false;
    }
}

function changeStake(step: number): void {
    stakeIndex.value = Math.min(
        props.stakes.length - 1,
        Math.max(0, stakeIndex.value + step),
    );
}

function endSession(): void {
    router.post(`/play/${props.session.id}/end`, { return: props.returnUrl });
}
</script>

<template>
    <Head :title="`${game.name} — demo`" />

    <div
        class="relative flex min-h-[calc(100svh-2.5rem)] flex-col overflow-hidden bg-plum-900 text-white"
    >
        <HeroScene :fade="false" />
        <div class="absolute inset-0 bg-plum-950/40" aria-hidden="true" />

        <header
            class="relative z-10 flex flex-wrap items-center justify-between gap-3 border-b border-plum-400 bg-plum-950/85 px-4 py-2.5 md:px-6"
        >
            <div class="flex items-center gap-3">
                <LuxBetLogo size="sm" subtitle="" />
                <span
                    class="rounded bg-demo px-2 py-0.5 text-[11px] font-bold tracking-wide text-demo-foreground"
                    >DEMO · SIMULATOR</span
                >
            </div>
            <dl class="flex gap-5 text-sm font-semibold md:gap-8">
                <div class="flex gap-1.5">
                    <dt>Balance:</dt>
                    <dd class="text-gold-400 tabular-nums">
                        {{ credits(balance) }}
                        <span class="text-[11px] text-demo-band-foreground"
                            >DEMO</span
                        >
                    </dd>
                </div>
                <div class="flex gap-1.5">
                    <dt>Stake:</dt>
                    <dd class="text-gold-400 tabular-nums">
                        {{ credits(stake) }}
                    </dd>
                </div>
                <div class="hidden gap-1.5 sm:flex">
                    <dt>Win:</dt>
                    <dd class="text-gold-400 tabular-nums">
                        {{ credits(lastRound?.payout ?? 0) }}
                    </dd>
                </div>
            </dl>
            <button
                type="button"
                class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-plum-600"
                @click="endSession"
            >
                <X class="size-4" aria-hidden="true" />
                End session
            </button>
        </header>

        <main
            class="relative z-10 flex flex-1 flex-col items-center justify-center gap-5 px-3 py-6"
        >
            <h1 class="sr-only">{{ game.name }} (demo)</h1>

            <div
                class="w-full max-w-[min(860px,calc((100svh-19rem)*1.62))] rounded-[22px] bg-linear-to-b from-gold-200 via-gold-600 to-gold-500 p-2.5 shadow-[0_0_60px_rgba(255,180,60,0.5),0_20px_50px_rgba(0,0,0,0.6)] md:p-3.5"
            >
                <div class="rounded-[14px] bg-[#3A0F14] p-2">
                    <div
                        v-if="isSlots"
                        class="grid grid-cols-5 gap-1.5"
                        aria-live="polite"
                    >
                        <div
                            v-for="(reel, r) in reels"
                            :key="r"
                            class="grid gap-1.5"
                        >
                            <div
                                v-for="(symbol, s) in reel"
                                :key="s"
                                class="flex aspect-square items-center justify-center rounded-lg bg-linear-to-b from-[#2B0D3F] via-[#1A0828] to-[#2B0D3F] p-2 transition-opacity"
                                :class="{ 'opacity-40': playing }"
                            >
                                <SlotSymbol
                                    :symbol="symbol"
                                    class="size-full"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex min-h-72 flex-col justify-center gap-6 rounded-lg bg-[radial-gradient(circle_at_50%_40%,#2E8C5A_0%,#12503A_60%,#0A2A1F_100%)] p-6"
                        aria-live="polite"
                    >
                        <div
                            v-for="(hand, label) in {
                                Dealer: tableCards?.dealer_cards ?? ['?', '?'],
                                You: tableCards?.player_cards ?? ['?', '?'],
                            }"
                            :key="label"
                            class="flex items-center gap-4"
                        >
                            <span
                                class="w-16 text-sm font-bold text-gold-200"
                                >{{ label }}</span
                            >
                            <span
                                v-for="(card, i) in hand"
                                :key="i"
                                class="flex h-24 w-16 items-center justify-center rounded-lg border border-[#C9C1B1] bg-white text-xl font-bold shadow-lg"
                                :class="
                                    /[♥♦]/.test(card)
                                        ? 'text-[#C81E3A]'
                                        : 'text-[#111]'
                                "
                                >{{ card }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <p
                class="min-h-7 text-center text-lg font-bold"
                :class="
                    lastRound?.result === 'win' ? 'text-gold-300' : 'text-white'
                "
                role="status"
            >
                {{ resultText }}
            </p>
            <p
                v-if="error"
                class="rounded-lg bg-plum-950/80 px-4 py-2 text-center text-sm text-[#F4A79D]"
                role="alert"
            >
                {{ error }}
                <Link
                    v-if="error.includes('Demo wallet')"
                    href="/demo-wallet"
                    class="ml-1 font-semibold text-gold-300 underline"
                    >Open Demo wallet</Link
                >
            </p>
        </main>

        <footer
            class="relative z-10 flex flex-wrap items-center justify-center gap-3 border-t border-plum-400 bg-plum-950/90 px-4 py-4"
        >
            <div
                class="flex h-13 items-center gap-1 rounded-full border-2 border-gold-600 bg-plum-850 px-1.5"
            >
                <button
                    type="button"
                    aria-label="Lower stake"
                    class="flex size-11 items-center justify-center rounded-full disabled:opacity-40"
                    :disabled="stakeIndex === 0 || playing"
                    @click="changeStake(-1)"
                >
                    <Minus class="size-5" aria-hidden="true" />
                </button>
                <div class="flex min-w-20 flex-col items-center leading-tight">
                    <span class="text-[10px] font-semibold text-plum-200"
                        >STAKE</span
                    >
                    <span class="text-lg font-extrabold tabular-nums">{{
                        credits(stake)
                    }}</span>
                </div>
                <button
                    type="button"
                    aria-label="Raise stake"
                    class="flex size-11 items-center justify-center rounded-full disabled:opacity-40"
                    :disabled="stakeIndex === stakes.length - 1 || playing"
                    @click="changeStake(1)"
                >
                    <Plus class="size-5" aria-hidden="true" />
                </button>
            </div>

            <button
                type="button"
                class="h-16 min-w-48 rounded-full border-[3px] border-[#B8F5A0] bg-linear-to-b from-[#7EF06A] via-[#2DBB2A] to-[#14801A] px-12 font-display text-3xl tracking-wide text-white shadow-[0_0_30px_rgba(80,230,80,0.45)] [text-shadow:0_2px_0_#0A5A10] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="playing || !sessionOpen"
                @click="play"
            >
                {{ playing ? '…' : isSlots ? 'SPIN' : 'DEAL' }}
            </button>

            <p class="w-full text-center text-xs text-plum-200">
                Local simulator — not a certified casino game · credits have no
                cash value ·
                <template v-if="sessionOpen"
                    >session ends in {{ minutesLeft }} min</template
                >
                <template v-else>session ended</template>
            </p>
        </footer>
    </div>
</template>
