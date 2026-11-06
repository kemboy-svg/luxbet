<script setup lang="ts">
import liveCasinoHost from '../../../images/live-casino-host.webp';
import type { GameCategory } from '@/types/luxbet';

defineProps<{
    category: GameCategory;
}>();
</script>

<template>
    <img
        v-if="category === 'live_casino'"
        :src="liveCasinoHost"
        alt=""
        width="256"
        height="256"
        loading="lazy"
        class="aspect-square w-auto rounded-full object-cover ring-2 ring-gold-500"
    />
    <svg v-else aria-hidden="true" viewBox="0 0 64 56">
        <g v-if="category === 'slots'">
            <rect
                x="10"
                y="8"
                width="44"
                height="40"
                rx="6"
                fill="#F5B31F"
                stroke="#8A5A06"
                stroke-width="2"
            />
            <rect x="15" y="16" width="34" height="18" rx="3" fill="#FFFFFF" />
            <circle cx="22" cy="25" r="4" fill="#E3223F" />
            <circle cx="32" cy="25" r="4" fill="#E3223F" />
            <circle cx="42" cy="25" r="4" fill="#E3223F" />
            <path
                d="M54 18 h5 v14"
                stroke="#8A5A06"
                stroke-width="3"
                fill="none"
            />
            <circle cx="59" cy="14" r="4" fill="#E3223F" />
            <rect x="20" y="38" width="24" height="5" rx="2" fill="#8A5A06" />
        </g>
        <g v-else-if="category === 'blackjack'">
            <rect
                x="10"
                y="8"
                width="28"
                height="40"
                rx="4"
                fill="#FFFFFF"
                stroke="#C9C1B1"
                transform="rotate(-12 24 28)"
            />
            <rect
                x="26"
                y="8"
                width="28"
                height="40"
                rx="4"
                fill="#FFFFFF"
                stroke="#C9C1B1"
                transform="rotate(10 40 28)"
            />
            <text
                x="31"
                y="36"
                font-family="Poppins, sans-serif"
                font-weight="800"
                font-size="15"
                fill="#111111"
            >
                21
            </text>
        </g>
        <g v-else-if="category === 'baccarat'">
            <rect
                x="12"
                y="8"
                width="28"
                height="40"
                rx="4"
                fill="#FFFFFF"
                stroke="#C9C1B1"
                transform="rotate(-8 26 28)"
            />
            <rect
                x="26"
                y="10"
                width="28"
                height="40"
                rx="4"
                fill="#FFFFFF"
                stroke="#C9C1B1"
                transform="rotate(12 40 30)"
            />
            <path
                d="M41 22 C 32 31 34 38 41 40 C 48 38 50 31 41 22 Z"
                fill="#E3223F"
            />
        </g>
        <g v-else-if="category === 'poker'">
            <circle
                cx="24"
                cy="30"
                r="17"
                fill="#1F5BD8"
                stroke="#FFFFFF"
                stroke-width="4"
                stroke-dasharray="7 6"
            />
            <circle
                cx="40"
                cy="26"
                r="17"
                fill="#E3223F"
                stroke="#FFFFFF"
                stroke-width="4"
                stroke-dasharray="7 6"
            />
            <circle cx="40" cy="26" r="7" fill="#FFFFFF" />
        </g>
        <g v-else>
            <path
                d="M44 6 C 54 10 58 18 56 26 L40 42 L26 28 L42 12 Z"
                fill="#F2F2F7"
                stroke="#6A6A80"
                stroke-width="1.5"
            />
            <circle cx="46" cy="18" r="5" fill="#3AA0FF" />
            <path d="M26 28 L16 30 L22 22 Z" fill="#E3223F" />
            <path d="M40 42 L38 52 L46 46 Z" fill="#E3223F" />
            <path
                d="M28 40 L14 54 M24 36 L10 46"
                stroke="#FFB13B"
                stroke-width="4"
                stroke-linecap="round"
            />
        </g>
    </svg>
</template>
