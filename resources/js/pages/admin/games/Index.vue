<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

type AdminGame = {
    id: string;
    name: string;
    slug: string;
    category: string;
    provider: string;
    provider_status: string;
    supports_demo: boolean;
    is_enabled: boolean;
    sort_order: number;
    public_status: 'playable' | 'demo_not_offered' | 'coming_soon' | 'hidden';
};

const props = defineProps<{
    games: AdminGame[];
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Games', href: '/admin/games' },
        ],
    },
});

const sortOrders = ref<Record<string, number>>(
    Object.fromEntries(props.games.map((g) => [g.id, g.sort_order])),
);

const statusLabel: Record<AdminGame['public_status'], string> = {
    playable: 'Playable (demo)',
    demo_not_offered: 'Listed, demo not offered',
    coming_soon: 'Listed as coming soon',
    hidden: 'Hidden',
};

function update(
    game: AdminGame,
    data: { sort_order?: number; is_enabled?: boolean },
): void {
    router.patch(`/admin/games/${game.id}`, data, { preserveScroll: true });
}
</script>

<template>
    <Head title="Games" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold">Game catalogue</h1>
            <p class="text-sm text-muted-foreground">
                A game is playable only when it is enabled, offers a demo, and
                its provider is in sandbox. Every change is audited.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[880px] border-collapse text-sm">
                <thead class="bg-card text-left text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Game</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Category
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Provider
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Demo</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Public status
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Order</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="game in games"
                        :key="game.id"
                        class="border-t border-border"
                    >
                        <td class="px-4 py-3">
                            <a
                                :href="`/games/${game.slug}`"
                                class="font-semibold text-foreground hover:text-primary"
                                >{{ game.name }}</a
                            >
                        </td>
                        <td class="px-4 py-3">{{ game.category }}</td>
                        <td class="px-4 py-3">
                            {{ game.provider }}
                            <span class="text-xs text-muted-foreground"
                                >({{ game.provider_status }})</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            {{ game.supports_demo ? 'Yes' : 'No' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ statusLabel[game.public_status] }}
                        </td>
                        <td class="px-4 py-3">
                            <form
                                v-if="canManage"
                                class="flex items-center gap-2"
                                @submit.prevent="
                                    update(game, {
                                        sort_order: sortOrders[game.id],
                                    })
                                "
                            >
                                <label :for="`order-${game.id}`" class="sr-only"
                                    >Sort order for {{ game.name }}</label
                                >
                                <input
                                    :id="`order-${game.id}`"
                                    v-model.number="sortOrders[game.id]"
                                    type="number"
                                    min="0"
                                    max="100000"
                                    class="h-9 w-20 rounded-md border border-input bg-background px-2"
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    variant="outline"
                                    :disabled="
                                        sortOrders[game.id] === game.sort_order
                                    "
                                    >Save</Button
                                >
                            </form>
                            <span v-else>{{ game.sort_order }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                v-if="canManage"
                                type="button"
                                size="sm"
                                :variant="
                                    game.is_enabled ? 'outline' : 'default'
                                "
                                @click="
                                    update(game, {
                                        is_enabled: !game.is_enabled,
                                    })
                                "
                                >{{
                                    game.is_enabled ? 'Disable' : 'Enable'
                                }}</Button
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
