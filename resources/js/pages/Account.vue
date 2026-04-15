<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, MailCheck, MailX, UserPlus, UsersRound } from 'lucide-vue-next';

interface AccountUser {
    id: number;
    name: string;
    email: string;
    role: string;
    email_verified_at: null | string;
    created_at: string;
}

const props = defineProps<{
    users: AccountUser[];
    roles: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Account',
        href: '/account',
    },
];

const form = useForm('AccountCreateUser', {
    name: '',
    email: '',
    role: 'user',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('account.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Account management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section
                class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-gradient-to-br from-white via-white to-neutral-50 dark:from-neutral-950 dark:via-neutral-950 dark:to-neutral-900"
            >
                <div class="flex flex-col gap-6 p-6 lg:flex-row lg:items-end lg:justify-between lg:p-8">
                    <div class="max-w-2xl space-y-3">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                        >
                            <UsersRound class="size-4" />
                            User management
                        </div>
                        <div class="space-y-2">
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground lg:text-3xl">Kelola account user aplikasi</h1>
                            <p class="text-sm leading-6 text-muted-foreground">
                                Lihat seluruh account dari tabel users dan tambahkan user baru tanpa meninggalkan halaman ini.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 self-start lg:min-w-72">
                        <div class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-4">
                            <p class="text-sm text-muted-foreground">Total users</p>
                            <p class="mt-2 text-2xl font-semibold text-foreground">{{ props.users.length }}</p>
                        </div>
                        <div class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-4">
                            <p class="text-sm text-muted-foreground">Verified</p>
                            <p class="mt-2 text-2xl font-semibold text-foreground">
                                {{ props.users.filter((user) => user.email_verified_at).length }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
                <section class="rounded-2xl border border-sidebar-border/70 bg-background">
                    <div class="flex items-center justify-between border-b border-sidebar-border/70 px-6 py-4">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">Daftar user</h2>
                            <p class="text-sm text-muted-foreground">Data terbaru dari tabel users.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-sidebar-border/70 text-sm">
                            <thead class="bg-muted/40 text-left text-muted-foreground">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Name</th>
                                    <th class="px-6 py-3 font-medium">Email</th>
                                    <th class="px-6 py-3 font-medium">Role</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th class="px-6 py-3 font-medium">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sidebar-border/70">
                                <tr v-for="user in props.users" :key="user.id" class="align-top">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-foreground">{{ user.name }}</div>
                                        <div class="text-xs text-muted-foreground">ID #{{ user.id }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ user.email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium capitalize text-primary">
                                            {{ user.role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
                                            :class="
                                                user.email_verified_at
                                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                            "
                                        >
                                            <MailCheck v-if="user.email_verified_at" class="size-3.5" />
                                            <MailX v-else class="size-3.5" />
                                            {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                </tr>
                                <tr v-if="props.users.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-muted-foreground">Belum ada user yang terdaftar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-sidebar-border/70 bg-background p-6">
                    <div class="flex flex-col gap-6">
                        <HeadingSmall title="Tambah user baru" description="Buat account baru dan simpan langsung ke tabel users." />

                        <form class="space-y-5" @submit.prevent="submit">
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" type="text" required autocomplete="name" placeholder="Full name" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email address</Label>
                                <Input id="email" v-model="form.email" type="email" required autocomplete="email" placeholder="email@example.com" />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="role">Role</Label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                >
                                    <option v-for="role in props.roles" :key="role" :value="role" class="capitalize">
                                        {{ role }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.role" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Password"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_confirmation">Confirm password</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>

                            <Button type="submit" class="w-full" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                                <UserPlus v-else class="size-4" />
                                Add user
                            </Button>

                            <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                User baru berhasil ditambahkan.
                            </p>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
