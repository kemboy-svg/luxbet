<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: { url: string | null; label: string; active: boolean }[];
}>();

const pageLabel = (label: string): string =>
    label
        .replace('&laquo; Previous', '‹ Previous')
        .replace('Next &raquo;', 'Next ›');
</script>

<template>
    <nav
        v-if="links.length > 3"
        aria-label="Pagination"
        class="flex flex-wrap gap-1"
    >
        <template v-for="link in links" :key="link.label">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="rounded-md border border-border px-3 py-1.5 text-sm"
                :class="{ 'bg-primary text-primary-foreground': link.active }"
                :aria-current="link.active ? 'page' : undefined"
                >{{ pageLabel(link.label) }}</Link
            >
            <span
                v-else
                class="rounded-md px-3 py-1.5 text-sm text-muted-foreground"
                >{{ pageLabel(link.label) }}</span
            >
        </template>
    </nav>
</template>
