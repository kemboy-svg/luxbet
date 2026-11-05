<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 58 44"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M4 14 L17 26 L29 4 L41 26 L54 14 L49 36 H9 Z M9 37 H49 V42 H9 Z"
        />
    </svg>
</template>
