<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldAlert, UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type StaffMember = {
    id: string;
    name: string;
    email: string;
    status: string;
    two_factor: boolean;
    roles: string[];
    is_self: boolean;
};

const props = defineProps<{
    staff: StaffMember[];
    roles: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Staff', href: '/admin/staff' }],
    },
});

const roleLabel = (value: string): string =>
    props.roles.find((r) => r.value === value)?.label ?? value;

const createForm = useForm({
    name: '',
    email: '',
    role: 'support_agent',
    reason: '',
});

function create(): void {
    createForm.post('/admin/staff', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

// One role change at a time: which member is being edited and how.
const editing = ref<{ id: string; mode: 'grant' | 'revoke' } | null>(null);
const roleForm = useForm({ role: '', reason: '' });

function open(member: StaffMember, mode: 'grant' | 'revoke'): void {
    editing.value = { id: member.id, mode };
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.role =
        mode === 'revoke'
            ? (member.roles[0] ?? '')
            : (props.roles.find((r) => !member.roles.includes(r.value))
                  ?.value ?? '');
}

function submitRole(member: StaffMember): void {
    const url = `/admin/staff/${member.id}/roles`;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
        },
    };

    if (editing.value?.mode === 'revoke') {
        roleForm.delete(url, options);
    } else {
        roleForm.post(url, options);
    }
}
</script>

<template>
    <Head title="Staff" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold">Staff</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Staff accounts are separate from player accounts. You can't
                change your own roles, and at least one super admin must remain.
                Every change is audited.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[820px] border-collapse text-sm">
                <thead class="bg-card text-left text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Name</th>
                        <th scope="col" class="px-4 py-3 font-medium">Roles</th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            Two-factor
                        </th>
                        <th scope="col" class="px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="member in staff" :key="member.id">
                        <tr class="border-t border-border">
                            <td class="px-4 py-3">
                                <span class="font-semibold">{{
                                    member.name
                                }}</span>
                                <span
                                    v-if="member.is_self"
                                    class="ml-1 text-xs text-muted-foreground"
                                    >(you)</span
                                >
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ member.email }}</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <span class="flex flex-wrap gap-1">
                                    <span
                                        v-for="role in member.roles"
                                        :key="role"
                                        class="text-plum-100 rounded bg-plum-600 px-2 py-0.5 text-xs font-semibold"
                                        >{{ roleLabel(role) }}</span
                                    >
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="member.two_factor"
                                    class="text-emerald-400"
                                    >On</span
                                >
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 text-amber-300"
                                    ><ShieldAlert
                                        class="size-4"
                                        aria-hidden="true"
                                    />Not set up</span
                                >
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span
                                    v-if="!member.is_self"
                                    class="inline-flex gap-2"
                                >
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        @click="open(member, 'grant')"
                                        >Add role</Button
                                    >
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        @click="open(member, 'revoke')"
                                        >Remove role</Button
                                    >
                                </span>
                            </td>
                        </tr>
                        <tr v-if="editing?.id === member.id" class="bg-card/50">
                            <td colspan="4" class="px-4 py-4">
                                <form
                                    class="flex flex-wrap items-end gap-3"
                                    @submit.prevent="submitRole(member)"
                                >
                                    <div class="grid gap-1.5">
                                        <Label :for="`role-${member.id}`">{{
                                            editing.mode === 'grant'
                                                ? 'Role to add'
                                                : 'Role to remove'
                                        }}</Label>
                                        <select
                                            :id="`role-${member.id}`"
                                            v-model="roleForm.role"
                                            class="h-9 min-w-48 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option
                                                v-for="role in editing.mode ===
                                                'grant'
                                                    ? roles.filter(
                                                          (r) =>
                                                              !member.roles.includes(
                                                                  r.value,
                                                              ),
                                                      )
                                                    : roles.filter((r) =>
                                                          member.roles.includes(
                                                              r.value,
                                                          ),
                                                      )"
                                                :key="role.value"
                                                :value="role.value"
                                            >
                                                {{ role.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid min-w-64 flex-1 gap-1.5">
                                        <Label :for="`reason-${member.id}`"
                                            >Reason</Label
                                        >
                                        <Input
                                            :id="`reason-${member.id}`"
                                            v-model="roleForm.reason"
                                            required
                                            minlength="5"
                                            maxlength="200"
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        :disabled="roleForm.processing"
                                        >{{
                                            editing.mode === 'grant'
                                                ? 'Add role'
                                                : 'Remove role'
                                        }}</Button
                                    >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        @click="editing = null"
                                        >Cancel</Button
                                    >
                                </form>
                                <InputError
                                    class="mt-2"
                                    :message="
                                        roleForm.errors.role ??
                                        roleForm.errors.reason
                                    "
                                />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <section
            aria-labelledby="create-heading"
            class="max-w-2xl rounded-xl border border-border bg-card p-5"
        >
            <h2
                id="create-heading"
                class="flex items-center gap-2 text-lg font-bold"
            >
                <UserPlus class="size-5" aria-hidden="true" /> Add a staff
                member
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Creates a new staff account and emails a link to set a password.
                They must set up two-factor authentication before using the
                admin area.
            </p>
            <form
                class="mt-4 grid gap-4 sm:grid-cols-2"
                @submit.prevent="create"
            >
                <div class="grid gap-1.5">
                    <Label for="staff-name">Name</Label>
                    <Input id="staff-name" v-model="createForm.name" required />
                    <InputError :message="createForm.errors.name" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="staff-email">Work email</Label>
                    <Input
                        id="staff-email"
                        v-model="createForm.email"
                        type="email"
                        required
                    />
                    <InputError :message="createForm.errors.email" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="staff-role">Role</Label>
                    <select
                        id="staff-role"
                        v-model="createForm.role"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option
                            v-for="r in roles"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
                        </option>
                    </select>
                    <InputError :message="createForm.errors.role" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="staff-reason">Reason</Label>
                    <Input
                        id="staff-reason"
                        v-model="createForm.reason"
                        required
                        minlength="5"
                        maxlength="200"
                    />
                    <InputError :message="createForm.errors.reason" />
                </div>
                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="createForm.processing"
                        >Create staff account</Button
                    >
                </div>
            </form>
        </section>
    </div>
</template>
