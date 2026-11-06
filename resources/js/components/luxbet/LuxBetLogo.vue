<script setup lang="ts">
import { useId } from 'vue';

const { size = 'md', subtitle = 'CASINO' } = defineProps<{
    size?: 'sm' | 'md' | 'lg';
    subtitle?: string;
}>();

const gradientId = `${useId()}-crown`;

const sizes = {
    sm: { crown: 'h-6 w-8', word: 'text-xl', sub: 'text-[8px]' },
    md: { crown: 'h-9 w-12', word: 'text-[28px]', sub: 'text-[10px]' },
    lg: { crown: 'h-11 w-14', word: 'text-[34px]', sub: 'text-[11px]' },
} as const;
</script>

<template>
    <span class="flex items-center gap-2.5">
        <svg
            aria-hidden="true"
            viewBox="0 0 58 44"
            :class="sizes[size].crown"
            class="shrink-0 drop-shadow-[0_2px_6px_rgba(245,179,31,0.35)]"
        >
            <defs>
                <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#FFE9A0" />
                    <stop offset="0.55" stop-color="#F5B31F" />
                    <stop offset="1" stop-color="#B9790A" />
                </linearGradient>
            </defs>
            <path
                d="M4 14 L17 26 L29 4 L41 26 L54 14 L49 36 H9 Z"
                :fill="`url(#${gradientId})`"
                stroke="#7A4E05"
                stroke-width="1.2"
            />
            <rect
                x="9"
                y="37"
                width="40"
                height="5"
                rx="2"
                :fill="`url(#${gradientId})`"
            />
            <circle cx="4" cy="13" r="3" fill="#FFE9A0" />
            <circle cx="29" cy="4" r="3" fill="#FFE9A0" />
            <circle cx="54" cy="13" r="3" fill="#FFE9A0" />
            <circle cx="29" cy="28" r="3.5" fill="#E2264D" />
        </svg>
        <span class="flex flex-col leading-none">
            <span
                :class="sizes[size].word"
                class="bg-linear-to-b from-gold-100 via-gold-500 to-gold-600 bg-clip-text font-extrabold tracking-tight text-transparent"
                >LuxBet</span
            >
            <span
                v-if="subtitle"
                :class="sizes[size].sub"
                class="mt-1 font-semibold tracking-[0.5em] text-gold-100"
                >{{ subtitle }}</span
            >
        </span>
    </span>
</template>
