<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

defineProps<{
    playerProfile: {
        username: string;
        country_code: string | null;
        preferred_locale: string;
    } | null;
}>();

// Common countries for LuxBet players; anything else can be left blank.
const countries: { code: string; name: string }[] = [
    { code: 'MU', name: 'Mauritius' },
    { code: 'KE', name: 'Kenya' },
    { code: 'ZA', name: 'South Africa' },
    { code: 'RE', name: 'Réunion' },
    { code: 'MG', name: 'Madagascar' },
    { code: 'SC', name: 'Seychelles' },
    { code: 'IN', name: 'India' },
    { code: 'FR', name: 'France' },
    { code: 'GB', name: 'United Kingdom' },
    { code: 'US', name: 'United States' },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name, username, email and preferences"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <template v-if="playerProfile">
                <div class="grid gap-2">
                    <Label for="username">Username</Label>
                    <Input
                        id="username"
                        class="mt-1 block w-full"
                        name="username"
                        :default-value="playerProfile.username"
                        required
                        autocomplete="nickname"
                    />
                    <InputError class="mt-2" :message="errors.username" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="country_code">Country (optional)</Label>
                        <select
                            id="country_code"
                            name="country_code"
                            class="mt-1 h-9 rounded-md border border-input bg-background px-3 text-sm"
                            :value="playerProfile.country_code ?? ''"
                        >
                            <option value="">Prefer not to say</option>
                            <option
                                v-for="country in countries"
                                :key="country.code"
                                :value="country.code"
                            >
                                {{ country.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="errors.country_code"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="preferred_locale">Language</Label>
                        <select
                            id="preferred_locale"
                            name="preferred_locale"
                            class="mt-1 h-9 rounded-md border border-input bg-background px-3 text-sm"
                            :value="playerProfile.preferred_locale"
                        >
                            <option value="en">English</option>
                            <option value="fr">Français</option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="errors.preferred_locale"
                        />
                    </div>
                </div>
            </template>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
