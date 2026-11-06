<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ProviderStatus = 'inactive' | 'sandbox' | 'active' | 'suspended';

type AdminProvider = {
    id: string;
    name: string;
    environment: 'sandbox' | 'production';
    status: ProviderStatus;
    supports_demo: boolean;
    is_simulator: boolean;
    can_serve_demo: boolean;
    games_count: number;
    credentials_configured: boolean;
    health: {
        available: boolean;
        latency_ms: number | null;
        message: string | null;
    };
};

defineProps<{
    providers: AdminProvider[];
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Providers', href: '/admin/providers' },
        ],
    },
});

const editingId = ref<string | null>(null);
const form = useForm<{ status: ProviderStatus; reason: string }>({
    status: 'sandbox',
    reason: '',
});

function startEditing(provider: AdminProvider): void {
    editingId.value = provider.id;
    form.reset();
    form.clearErrors();
    form.status = provider.status === 'sandbox' ? 'suspended' : 'sandbox';
}

function save(provider: AdminProvider): void {
    form.patch(`/admin/providers/${provider.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Providers" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold">Game providers</h1>
            <p class="text-sm text-muted-foreground">
                Only sandbox endpoints are used in demo mode. Provider
                credentials are never shown here.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section
                v-for="provider in providers"
                :key="provider.id"
                class="rounded-xl border border-border bg-card p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ provider.name }}
                        </h2>
                        <p
                            v-if="provider.is_simulator"
                            class="text-xs text-muted-foreground"
                        >
                            Local simulator — not a certified casino game
                            provider
                        </p>
                    </div>
                    <span
                        class="rounded px-2 py-0.5 text-xs font-bold"
                        :class="
                            provider.can_serve_demo
                                ? 'bg-[#163247] text-[#8fd3f5]'
                                : 'bg-[#4a2a12] text-[#f6c08a]'
                        "
                        >{{
                            provider.can_serve_demo
                                ? 'Serving demos'
                                : 'Not serving'
                        }}</span
                    >
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Status</dt>
                        <dd class="font-semibold capitalize">
                            {{ provider.status }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Environment</dt>
                        <dd class="font-semibold capitalize">
                            {{ provider.environment }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Games</dt>
                        <dd class="font-semibold">
                            {{ provider.games_count }}
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-muted-foreground">Health</dt>
                        <dd class="font-semibold">
                            <span
                                :class="
                                    provider.health.available
                                        ? 'text-[#8fd3f5]'
                                        : 'text-[#f6c08a]'
                                "
                                >{{
                                    provider.health.available
                                        ? '● Reachable'
                                        : '○ Unavailable'
                                }}</span
                            >
                            <span
                                v-if="provider.health.message"
                                class="ml-1 font-normal text-muted-foreground"
                                >— {{ provider.health.message }}</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Credentials</dt>
                        <dd class="font-semibold">
                            {{
                                provider.credentials_configured
                                    ? 'Configured'
                                    : 'None'
                            }}
                        </dd>
                    </div>
                </dl>

                <template v-if="canManage">
                    <Button
                        v-if="editingId !== provider.id"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="mt-4"
                        @click="startEditing(provider)"
                        >Change status</Button
                    >
                    <form
                        v-else
                        class="mt-4 grid gap-3"
                        @submit.prevent="save(provider)"
                    >
                        <div class="grid gap-1.5">
                            <Label :for="`status-${provider.id}`"
                                >New status</Label
                            >
                            <select
                                :id="`status-${provider.id}`"
                                v-model="form.status"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="sandbox">Sandbox</option>
                                <option value="suspended">Suspended</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label :for="`reason-${provider.id}`"
                                >Reason (recorded in the audit log)</Label
                            >
                            <Input
                                :id="`reason-${provider.id}`"
                                v-model="form.reason"
                                required
                                minlength="5"
                                maxlength="500"
                            />
                            <InputError :message="form.errors.reason" />
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing"
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
                </template>
            </section>
        </div>
    </div>
</template>
