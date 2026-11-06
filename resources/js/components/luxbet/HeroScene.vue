<script setup lang="ts">
import { useId } from 'vue';

/**
 * Decorative sunset scene: Le Morne at dusk, palms, a roulette wheel,
 * cards, dice and chips. Pure SVG, no image files.
 */
const { fade = true } = defineProps<{
    /** Darken the left side so overlaid text stays readable. */
    fade?: boolean;
}>();

const uid = useId();
const names = ['sky', 'sun', 'sea', 'mtn', 'fade', 'gold', 'felt'] as const;
const ids = Object.fromEntries(
    names.map((name) => [name, `${uid}-${name}`]),
) as Record<(typeof names)[number], string>;
const url = (id: string): string => `url(#${id})`;
</script>

<template>
    <svg
        aria-hidden="true"
        viewBox="0 0 1360 540"
        preserveAspectRatio="xMidYMid slice"
        class="absolute inset-0 h-full w-full"
    >
        <defs>
            <linearGradient :id="ids.sky" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#1E0A3C"></stop>
                <stop offset="0.45" stop-color="#5B1F78"></stop>
                <stop offset="0.72" stop-color="#C2477A"></stop>
                <stop offset="0.86" stop-color="#F59A45"></stop>
            </linearGradient>
            <radialGradient :id="ids.sun" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#FFF0B3"></stop>
                <stop offset="0.6" stop-color="#FFC15A"></stop>
                <stop offset="1" stop-color="#FF8A3D" stop-opacity="0"></stop>
            </radialGradient>
            <linearGradient :id="ids.sea" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#7A3A86"></stop>
                <stop offset="1" stop-color="#160A2A"></stop>
            </linearGradient>
            <linearGradient :id="ids.mtn" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#4A2160"></stop>
                <stop offset="1" stop-color="#21102F"></stop>
            </linearGradient>
            <linearGradient :id="ids.fade" x1="0" y1="0" x2="1" y2="0">
                <stop
                    offset="0"
                    stop-color="#12081F"
                    stop-opacity="0.92"
                ></stop>
                <stop
                    offset="0.42"
                    stop-color="#12081F"
                    stop-opacity="0.55"
                ></stop>
                <stop
                    offset="0.65"
                    stop-color="#12081F"
                    stop-opacity="0"
                ></stop>
            </linearGradient>
            <linearGradient :id="ids.gold" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#FFE9A0"></stop>
                <stop offset="1" stop-color="#C88A0E"></stop>
            </linearGradient>
            <radialGradient :id="ids.felt" cx="0.5" cy="0.5" r="0.5">
                <stop offset="0" stop-color="#3B1C14"></stop>
                <stop offset="1" stop-color="#140806"></stop>
            </radialGradient>
        </defs>
        <rect width="1360" height="540" :fill="url(ids.sky)"></rect>
        <circle cx="1010" cy="330" r="150" :fill="url(ids.sun)"></circle>
        <path
            d="M700 380 L820 330 L900 290 L950 220 L985 160 L1030 140 L1075 150 L1110 200 L1160 255 L1240 300 L1360 330 L1360 400 L700 400 Z"
            :fill="url(ids.mtn)"
        ></path>
        <rect y="385" width="1360" height="155" :fill="url(ids.sea)"></rect>
        <g
            stroke="#FFC46A"
            stroke-width="2"
            stroke-linecap="round"
            opacity="0.7"
        >
            <path d="M960 400 H1060"></path>
            <path d="M975 414 H1045"></path>
            <path d="M990 428 H1030"></path>
            <path d="M1000 442 H1020"></path>
        </g>
        <g fill="#0D0518">
            <path
                d="M610 540 C 620 450 640 380 668 300 L676 302 C 652 380 636 450 630 540 Z"
            ></path>
            <path
                d="M672 300 C 630 270 590 275 560 300 C 600 285 640 290 672 304 Z"
            ></path>
            <path
                d="M672 300 C 700 255 745 250 780 270 C 740 268 705 280 674 306 Z"
            ></path>
            <path
                d="M672 300 C 640 250 650 215 668 190 C 666 230 672 265 676 302 Z"
            ></path>
            <path
                d="M672 300 C 712 290 750 310 770 345 C 740 320 708 312 674 306 Z"
            ></path>
            <path
                d="M672 300 C 640 300 610 320 596 350 C 620 328 646 316 672 306 Z"
            ></path>
            <path
                d="M1330 540 C 1320 440 1300 370 1270 300 L1278 297 C 1306 368 1330 440 1342 540 Z"
            ></path>
            <path
                d="M1274 298 C 1240 262 1200 262 1170 285 C 1205 276 1240 282 1272 304 Z"
            ></path>
            <path
                d="M1274 298 C 1300 255 1340 245 1360 252 L1360 268 C 1330 266 1300 280 1278 304 Z"
            ></path>
            <path
                d="M1274 298 C 1250 300 1222 320 1208 352 C 1232 330 1252 318 1276 306 Z"
            ></path>
        </g>
        <ellipse
            cx="1170"
            cy="520"
            rx="240"
            ry="92"
            fill="#2A0F08"
            :stroke="url(ids.gold)"
            stroke-width="6"
        ></ellipse>
        <ellipse
            cx="1170"
            cy="512"
            rx="200"
            ry="72"
            :fill="url(ids.felt)"
        ></ellipse>
        <g stroke-width="14" fill="none" stroke-dasharray="22 22">
            <ellipse
                cx="1170"
                cy="512"
                rx="180"
                ry="62"
                stroke="#C81E3A"
            ></ellipse>
            <ellipse
                cx="1170"
                cy="512"
                rx="180"
                ry="62"
                stroke="#140808"
                stroke-dashoffset="22"
            ></ellipse>
        </g>
        <ellipse
            cx="1170"
            cy="512"
            rx="90"
            ry="30"
            fill="#3A1A0E"
            :stroke="url(ids.gold)"
            stroke-width="4"
        ></ellipse>
        <circle cx="1170" cy="506" r="12" :fill="url(ids.gold)"></circle>
        <g transform="translate(860 300) rotate(-14)">
            <rect
                x="0"
                y="0"
                width="92"
                height="128"
                rx="10"
                fill="#FFFFFF"
                stroke="#D9D2C3"
            ></rect>
            <text
                x="12"
                y="30"
                font-family="Poppins"
                font-weight="700"
                font-size="24"
                fill="#111111"
            >
                A
            </text>
            <path
                d="M46 52 C 30 70 30 86 44 86 C 40 92 38 96 36 100 H56 C 54 96 52 92 48 86 C 62 86 62 70 46 52 Z"
                fill="#111111"
            ></path>
        </g>
        <g transform="translate(920 290) rotate(8)">
            <rect
                x="0"
                y="0"
                width="92"
                height="128"
                rx="10"
                fill="#FFFFFF"
                stroke="#D9D2C3"
            ></rect>
            <text
                x="12"
                y="30"
                font-family="Poppins"
                font-weight="700"
                font-size="24"
                fill="#C81E3A"
            >
                K
            </text>
            <path
                d="M46 96 C 20 72 26 50 40 50 C 44 50 46 54 46 58 C 46 54 48 50 52 50 C 66 50 72 72 46 96 Z"
                fill="#C81E3A"
            ></path>
        </g>
        <g transform="translate(870 440) rotate(-10)">
            <rect width="58" height="58" rx="10" fill="#E3223F"></rect>
            <circle cx="16" cy="16" r="5" fill="#FFFFFF"></circle>
            <circle cx="29" cy="29" r="5" fill="#FFFFFF"></circle>
            <circle cx="42" cy="42" r="5" fill="#FFFFFF"></circle>
        </g>
        <g transform="translate(940 470) rotate(14)">
            <rect width="50" height="50" rx="9" fill="#F4F0E8"></rect>
            <circle cx="14" cy="14" r="4.5" fill="#C81E3A"></circle>
            <circle cx="36" cy="36" r="4.5" fill="#C81E3A"></circle>
            <circle cx="36" cy="14" r="4.5" fill="#C81E3A"></circle>
            <circle cx="14" cy="36" r="4.5" fill="#C81E3A"></circle>
        </g>
        <g>
            <ellipse
                cx="780"
                cy="500"
                rx="42"
                ry="14"
                fill="#1A1A1A"
                :stroke="url(ids.gold)"
                stroke-width="5"
            ></ellipse>
            <ellipse
                cx="780"
                cy="486"
                rx="42"
                ry="14"
                fill="#1A1A1A"
                :stroke="url(ids.gold)"
                stroke-width="5"
            ></ellipse>
            <ellipse
                cx="780"
                cy="472"
                rx="42"
                ry="14"
                fill="#C81E3A"
                stroke="#FFFFFF"
                stroke-width="5"
                stroke-dasharray="10 8"
            ></ellipse>
        </g>
        <rect
            v-if="fade"
            width="1360"
            height="540"
            :fill="url(ids.fade)"
        ></rect>
    </svg>
</template>
