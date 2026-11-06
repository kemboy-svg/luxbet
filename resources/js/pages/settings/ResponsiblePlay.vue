<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Timer } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    reminderMinutes: number | null;
    marketingOptIn: boolean;
    reminderOptions: number[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Responsible play',
                href: '/settings/responsible-play',
            },
        ],
    },
});

const form = useForm<{
    demo_session_reminder_minutes: number | null;
    marketing_opt_in: boolean;
}>({
    demo_session_reminder_minutes: props.reminderMinutes,
    marketing_opt_in: props.marketingOptIn,
});

function save(): void {
    form.patch('/settings/responsible-play', { preserveScroll: true });
}
</script>

<template>
    <Head title="Responsible play" />

    <h1 class="sr-only">Responsible play settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Responsible play"
            description="LuxBet is for entertainment, played with demo credits that have no cash value."
        />

        <form class="space-y-6" @submit.prevent="save">
            <div class="grid gap-2">
                <Label for="reminder">Session reminder</Label>
                <p class="text-sm text-muted-foreground">
                    While you play, we'll remind you how long you've been
                    playing so you can take a break.
                </p>
                <div class="flex items-center gap-3">
                    <Timer
                        class="size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <select
                        id="reminder"
                        v-model="form.demo_session_reminder_minutes"
                        class="h-9 w-56 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option
                            v-for="minutes in reminderOptions"
                            :key="minutes"
                            :value="minutes"
                        >
                            Every {{ minutes }} minutes
                        </option>
                        <option :value="null">No reminders</option>
                    </select>
                </div>
                <InputError
                    :message="form.errors.demo_session_reminder_minutes"
                />
            </div>

            <div class="flex items-start gap-3">
                <input
                    id="marketing"
                    v-model="form.marketing_opt_in"
                    type="checkbox"
                    class="mt-0.5 size-5 shrink-0 accent-primary"
                />
                <div>
                    <Label for="marketing" class="font-normal"
                        >Email me product updates</Label
                    >
                    <p class="text-sm text-muted-foreground">
                        Off unless you turn it on. You can change this at any
                        time.
                    </p>
                </div>
            </div>
            <InputError :message="form.errors.marketing_opt_in" />

            <Button type="submit" :disabled="form.processing">Save</Button>
        </form>

        <div class="rounded-xl border border-border bg-card p-4 text-sm">
            <p class="font-semibold">Need a longer break?</p>
            <p class="mt-1 text-muted-foreground">
                You can close your account at any time from Profile settings.
            </p>
        </div>
    </div>
</template>
