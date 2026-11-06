<script setup lang="ts">
defineProps<{
    symbol: string;
}>();
</script>

<template>
    <svg
        v-if="symbol === 'seven'"
        viewBox="0 0 100 100"
        role="img"
        aria-label="Seven"
    >
        <path
            d="M18 16 H84 L52 90 H28 L58 36 H18 Z"
            fill="#F0253F"
            stroke="#7A0A18"
            stroke-width="4"
            stroke-linejoin="round"
        />
        <path
            d="M24 22 H76"
            stroke="#FF8A98"
            stroke-width="4"
            stroke-linecap="round"
        />
    </svg>
    <svg
        v-else-if="symbol === 'bell'"
        viewBox="0 0 100 100"
        role="img"
        aria-label="Bell"
    >
        <path
            d="M50 12 C 30 12 26 34 26 52 L16 72 H84 L74 52 C 74 34 70 12 50 12 Z"
            fill="#F5B31F"
            stroke="#8A5A06"
            stroke-width="3"
        />
        <circle cx="50" cy="80" r="8" fill="#C88A0E" />
        <path
            d="M36 30 C 34 40 34 48 34 56"
            stroke="#FFE9A0"
            stroke-width="4"
            stroke-linecap="round"
            fill="none"
        />
    </svg>
    <svg
        v-else-if="symbol === 'cherry'"
        viewBox="0 0 100 100"
        role="img"
        aria-label="Cherries"
    >
        <path
            d="M34 60 C 40 36 52 22 70 14 M64 62 C 64 40 66 26 70 14"
            stroke="#2E8C3A"
            stroke-width="4"
            fill="none"
            stroke-linecap="round"
        />
        <path
            d="M70 14 C 80 10 90 16 92 26 C 82 28 74 22 70 14 Z"
            fill="#3FB34F"
        />
        <circle
            cx="32"
            cy="70"
            r="17"
            fill="#E3223F"
            stroke="#7A0A18"
            stroke-width="3"
        />
        <circle
            cx="66"
            cy="72"
            r="17"
            fill="#E3223F"
            stroke="#7A0A18"
            stroke-width="3"
        />
        <circle cx="26" cy="64" r="4" fill="#FF9AA8" />
        <circle cx="60" cy="66" r="4" fill="#FF9AA8" />
    </svg>
    <svg
        v-else-if="symbol === 'diamond'"
        viewBox="0 0 100 100"
        role="img"
        aria-label="Diamond"
    >
        <path
            d="M16 38 L32 18 H68 L84 38 L50 86 Z"
            fill="#3A8BFF"
            stroke="#0E3C8A"
            stroke-width="3"
            stroke-linejoin="round"
        />
        <path
            d="M16 38 H84 M32 18 L42 38 L50 86 L58 38 L68 18 M42 38 L50 18 L58 38"
            stroke="#BFE0FF"
            stroke-width="2"
            fill="none"
        />
    </svg>
    <svg
        v-else-if="symbol === 'bar'"
        viewBox="0 0 100 100"
        role="img"
        aria-label="Bar"
    >
        <rect
            x="4"
            y="27"
            width="92"
            height="46"
            rx="8"
            fill="#F5B31F"
            stroke="#8A5A06"
            stroke-width="3"
        />
        <rect x="10" y="33" width="80" height="34" rx="5" fill="#1A0A0A" />
        <text
            x="50"
            y="60"
            text-anchor="middle"
            font-family="Poppins, sans-serif"
            font-weight="800"
            font-size="26"
            fill="#FFD447"
        >
            BAR
        </text>
    </svg>
    <svg
        v-else-if="symbol === 'crown'"
        viewBox="0 0 58 50"
        role="img"
        aria-label="Crown"
    >
        <path
            d="M4 16 L17 28 L29 6 L41 28 L54 16 L49 38 H9 Z"
            fill="#F5B31F"
            stroke="#8A5A06"
            stroke-width="1.5"
        />
        <rect x="9" y="39" width="40" height="6" rx="2" fill="#C88A0E" />
        <circle cx="29" cy="30" r="4" fill="#E3223F" />
        <circle cx="17" cy="33" r="2.5" fill="#3A8BFF" />
        <circle cx="41" cy="33" r="2.5" fill="#3A8BFF" />
    </svg>
    <svg v-else viewBox="0 0 100 100" role="img" aria-label="Heart">
        <path
            d="M50 88 C 14 62 6 42 18 26 C 28 14 44 16 50 30 C 56 16 72 14 82 26 C 94 42 86 62 50 88 Z"
            fill="#E3223F"
            stroke="#7A0A18"
            stroke-width="4"
        />
        <path
            d="M26 34 C 28 28 34 26 38 28"
            stroke="#FF9AA8"
            stroke-width="4"
            stroke-linecap="round"
            fill="none"
        />
    </svg>
</template>
