<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PlayerStatus = 'active' | 'suspended' | 'closed' | 'pending';

type PlayerRow = {
    id: string;
    username: string | null;
    email: string;
    status: PlayerStatus;
    email_verified: boolean;
    joined_at: string | null;
    can_change_status: boolean;
};

type PaginationLink = { url: string | null; label: string; active: boolean };

const props = defineProps<{
    players: {
        data: PlayerRow[];
        links: PaginationLink[];
        total: number;
    };
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Players', href: '/admin/players' },
        ],
    },
});

const search = ref(props.filters.search);
const editingId = ref<string | null>(null);

const statusForm = useForm<{ status: PlayerStatus; reason: string }>({
    status: 'suspended',
    reason: '',
});

const statusClasses: Record<PlayerStatus, string> = {
    active: 'bg-[#163247] text-[#8fd3f5]',
    suspended: 'bg-[#4a2a12] text-[#f6c08a]',
    closed: 'bg-plum-600 text-plum-200',
    pending: 'bg-plum-600 text-plum-200',
};

function submitSearch(): void {
    router.get('/admin/players', search.value ? { search: search.value } : {}, {
        preserveState: true,
        replace: true,
    });
}

function startEditing(player: PlayerRow): void {
    editingId.value = player.id;
    statusForm.reset();
    statusForm.clearErrors();
    statusForm.status = player.status === 'active' ? 'suspended' : 'active';
}

function saveStatus(player: PlayerRow): void {
    statusForm.patch(`/admin/players/${player.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

// Laravel's pagination labels contain HTML entities; decode the two it uses
// instead of rendering raw HTML.
function pageLabel(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '—';
}
</script>

<template>
    <Head title="Players" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Players</h1>
                <p class="text-sm text-muted-foreground">
                    {{ players.total.toLocaleString() }} demo accounts
                </p>
            </div>
            <form
                class="flex items-end gap-2"
                role="search"
                @submit.prevent="submitSearch"
            >
                <div class="grid gap-1.5">
                    <Label for="player-search">Search players</Label>
                    <Input
                        id="player-search"
                        v-model="search"
                        type="search"
                        placeholder="Username or email"
                        class="w-64"
                    />
                </div>
                <Button type="submit" variant="secondary">Search</Button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[760px] border-collapse text-sm">
                <thead class="bg-card text-left text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Username
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">Email</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Status
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Joined
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="player in players.data" :key="player.id">
                        <tr class="border-t border-border">
                            <td class="px-4 py-3 font-semibold">
                                {{ player.username ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ player.email }}
                                <span
                                    v-if="!player.email_verified"
                                    class="ml-1 text-xs text-muted-foreground"
                                    >(unverified)</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded px-2 py-0.5 text-xs font-bold capitalize"
                                    :class="statusClasses[player.status]"
                                    >{{ player.status }}</span
                                >
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(player.joined_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    v-if="
                                        player.can_change_status &&
                                        editingId !== player.id
                                    "
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="startEditing(player)"
                                    >Change status</Button
                                >
                            </td>
                        </tr>
                        <tr v-if="editingId === player.id">
                            <td colspan="5" class="bg-card px-4 py-4">
                                <form
                                    class="flex flex-wrap items-start gap-4"
                                    @submit.prevent="saveStatus(player)"
                                >
                                    <div class="grid gap-1.5">
                                        <Label :for="`status-${player.id}`"
                                            >New status</Label
                                        >
                                        <select
                                            :id="`status-${player.id}`"
                                            v-model="statusForm.status"
                                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="active">
                                                Active
                                            </option>
                                            <option value="suspended">
                                                Suspended
                                            </option>
                                            <option value="closed">
                                                Closed
                                            </option>
                                        </select>
                                        <InputError
                                            :message="statusForm.errors.status"
                                        />
                                    </div>
                                    <div class="grid min-w-64 flex-1 gap-1.5">
                                        <Label :for="`reason-${player.id}`"
                                            >Reason (recorded in the audit
                                            log)</Label
                                        >
                                        <Input
                                            :id="`reason-${player.id}`"
                                            v-model="statusForm.reason"
                                            required
                                            minlength="5"
                                            maxlength="500"
                                        />
                                        <InputError
                                            :message="statusForm.errors.reason"
                                        />
                                    </div>
                                    <div class="flex gap-2 self-end">
                                        <Button
                                            type="submit"
                                            :disabled="statusForm.processing"
                                            >Save</Button
                                        >
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            @click="editingId = null"
                                            >Cancel</Button
                                        >
                                    </div>
                                </form>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="players.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No players found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav
            v-if="players.links.length > 3"
            aria-label="Pagination"
            class="flex flex-wrap gap-1"
        >
            <template v-for="link in players.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="rounded-md border border-border px-3 py-1.5 text-sm"
                    :class="{
                        'bg-primary text-primary-foreground': link.active,
                    }"
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
    </div>
</template>
