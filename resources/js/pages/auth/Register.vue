<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create a demo account',
        description: 'You get free demo credits. They have no cash value.',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="username">Username</Label>
                <Input
                    id="username"
                    type="text"
                    required
                    :tabindex="2"
                    autocomplete="username"
                    name="username"
                    placeholder="e.g. amara_k"
                    aria-describedby="username-hint"
                />
                <p id="username-hint" class="text-xs text-muted-foreground">
                    3–20 letters, numbers or underscores. Shown to other
                    players.
                </p>
                <InputError :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="grid gap-3">
                <div class="flex items-start gap-3">
                    <input
                        id="age_confirmed"
                        type="checkbox"
                        name="age_confirmed"
                        required
                        :tabindex="6"
                        class="mt-0.5 size-5 shrink-0 accent-primary"
                    />
                    <Label for="age_confirmed" class="font-normal"
                        >I'm 18 or older</Label
                    >
                </div>
                <InputError :message="errors.age_confirmed" />

                <div class="flex items-start gap-3">
                    <input
                        id="terms_accepted"
                        type="checkbox"
                        name="terms_accepted"
                        required
                        :tabindex="7"
                        class="mt-0.5 size-5 shrink-0 accent-primary"
                    />
                    <Label for="terms_accepted" class="leading-snug font-normal"
                        >I agree to the Terms of demo use and have read the
                        Privacy notice</Label
                    >
                </div>
                <InputError :message="errors.terms_accepted" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="8"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create demo account
            </Button>

            <p class="text-center text-xs text-muted-foreground">
                No card details, ID documents or payments — ever.
            </p>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="9"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
