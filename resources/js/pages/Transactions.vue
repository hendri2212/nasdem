<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, Landmark, LoaderCircle, ReceiptText, Search, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

interface TransactionItem {
    id: number;
    transaction_date: string;
    type: 'credit' | 'debit';
    location: 'bank' | 'cash';
    description: string;
    amount: number;
}

interface Totals {
    cash_balance: number;
    bank_balance: number;
    net_movement: number;
}

const props = defineProps<{
    transactions: TransactionItem[];
    totals: Totals;
    types: Array<'credit' | 'debit'>;
    locations: Array<'bank' | 'cash'>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaction',
        href: '/transactions',
    },
];

const form = useForm({
    transaction_date: '',
    type: 'credit' as 'credit' | 'debit',
    location: 'cash' as 'cash' | 'bank',
    description: '',
    amount: '',
});

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (value: string): string => {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const summaryCards = computed(() => [
    {
        title: 'Cash Balance',
        value: formatCurrency(props.totals.cash_balance),
        description: 'Pergerakan dana transaksi kas',
        icon: Wallet,
        accent: 'from-emerald-500/15 via-emerald-500/5 to-transparent text-emerald-600',
    },
    {
        title: 'Bank Balance',
        value: formatCurrency(props.totals.bank_balance),
        description: 'Pergerakan dana transaksi bank',
        icon: Landmark,
        accent: 'from-sky-500/15 via-sky-500/5 to-transparent text-sky-600',
    },
    {
        title: 'Net Movement',
        value: formatCurrency(props.totals.net_movement),
        description: 'Total pemasukan dikurangi pengeluaran dari semua transaksi',
        icon: ArrowUpRight,
        accent: 'from-amber-500/15 via-amber-500/5 to-transparent text-amber-600',
    },
]);

const typeClasses: Record<TransactionItem['type'], string> = {
    credit: 'bg-emerald-500/12 text-emerald-700',
    debit: 'bg-rose-500/12 text-rose-700',
};

const submit = (): void => {
    form.transform((data) => ({
        ...data,
        amount: Number(data.amount),
    })).post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.type = 'credit';
            form.location = 'cash';
        },
    });
};
</script>

<template>
    <Head title="Transaction" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <section
                class="overflow-hidden rounded-[2rem] border border-sidebar-border/70 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.16),_transparent_34%),linear-gradient(135deg,_rgba(255,255,255,0.98),_rgba(248,250,252,0.94))]"
            >
                <div class="flex flex-col gap-6 p-6 lg:p-8">
                    <div class="max-w-4xl space-y-4">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-sky-500/20 bg-sky-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-700"
                        >
                            <ReceiptText class="size-4" />
                            Transaction desk
                        </div>

                        <div>
                            <h1 class="text-3xl font-semibold tracking-tight text-foreground md:text-4xl">Pantau dana masuk dan keluar</h1>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div
                            v-for="card in summaryCards"
                            :key="card.title"
                            class="rounded-3xl border border-white/60 bg-white/80 p-4 shadow-sm backdrop-blur"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-2">
                                    <p class="text-sm text-muted-foreground">{{ card.title }}</p>
                                    <p class="text-xl font-semibold text-foreground">{{ card.value }}</p>
                                </div>
                                <div :class="['rounded-2xl bg-gradient-to-br p-3', card.accent]">
                                    <component :is="card.icon" class="size-5" />
                                </div>
                            </div>
                            <p class="mt-3 text-xs leading-5 text-muted-foreground">{{ card.description }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.6fr_0.95fr]">
                <Card class="rounded-[1.75rem] border-sidebar-border/70 shadow-sm">
                    <CardHeader class="gap-4 border-b border-sidebar-border/70 pb-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="space-y-1">
                                <CardTitle class="text-xl">Recent transactions</CardTitle>
                                <CardDescription>Data terbaru dari tabel transaksi.</CardDescription>
                            </div>

                            <div class="relative w-full max-w-sm">
                                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input class="pl-9" placeholder="Pencarian akan dihubungkan berikutnya" disabled />
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-sidebar-border/70 text-sm">
                                <thead class="bg-muted/40 text-left text-muted-foreground">
                                    <tr>
                                        <th class="px-6 py-4 font-medium">Reference</th>
                                        <th class="px-6 py-4 font-medium">Date</th>
                                        <th class="px-6 py-4 font-medium">Type</th>
                                        <th class="px-6 py-4 font-medium">Location</th>
                                        <th class="px-6 py-4 font-medium">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-sidebar-border/70">
                                    <tr v-for="transaction in props.transactions" :key="transaction.id" class="bg-background/80 align-top">
                                        <td class="px-6 py-5">
                                            <div class="font-medium text-foreground">TRX-{{ String(transaction.id).padStart(5, '0') }}</div>
                                            <div class="mt-1 max-w-xs text-xs leading-5 text-muted-foreground">
                                                {{ transaction.description }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-muted-foreground">{{ formatDate(transaction.transaction_date) }}</td>
                                        <td class="px-6 py-5">
                                            <span
                                                :class="[
                                                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize',
                                                    typeClasses[transaction.type],
                                                ]"
                                            >
                                                <ArrowUpRight v-if="transaction.type === 'credit'" class="mr-1 size-3.5" />
                                                <ArrowDownLeft v-else class="mr-1 size-3.5" />
                                                {{ transaction.type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span
                                                class="inline-flex rounded-full bg-secondary px-3 py-1 text-xs font-medium capitalize text-secondary-foreground"
                                            >
                                                {{ transaction.location }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 font-medium text-foreground">{{ formatCurrency(transaction.amount) }}</td>
                                    </tr>
                                    <tr v-if="props.transactions.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                            Belum ada transaksi yang tersimpan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <div class="grid gap-6">
                    <Card class="rounded-[1.75rem] border-sidebar-border/70 shadow-sm">
                        <CardHeader>
                            <CardTitle>Create transaction</CardTitle>
                            <CardDescription>Simpan transaksi baru langsung ke tabel transaksi.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form class="space-y-5" @submit.prevent="submit">
                                <div class="grid gap-2">
                                    <Label for="transaction-date">Date</Label>
                                    <Input id="transaction-date" v-model="form.transaction_date" type="datetime-local" />
                                    <InputError :message="form.errors.transaction_date" />
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="transaction-type">Type</Label>
                                        <select
                                            id="transaction-type"
                                            v-model="form.type"
                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background"
                                        >
                                            <option v-for="type in props.types" :key="type" :value="type" class="capitalize">
                                                {{ type }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.type" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="transaction-location">Location</Label>
                                        <select
                                            id="transaction-location"
                                            v-model="form.location"
                                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background"
                                        >
                                            <option v-for="location in props.locations" :key="location" :value="location" class="capitalize">
                                                {{ location }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.location" />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="transaction-description">Description</Label>
                                    <Input
                                        id="transaction-description"
                                        v-model="form.description"
                                        type="text"
                                        placeholder="Operational support, transfer, donation, etc."
                                    />
                                    <InputError :message="form.errors.description" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="transaction-amount">Amount</Label>
                                    <Input id="transaction-amount" v-model="form.amount" type="number" min="0" placeholder="0" />
                                    <InputError :message="form.errors.amount" />
                                </div>

                                <Button type="submit" class="w-full" :disabled="form.processing">
                                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                                    <ReceiptText v-else class="size-4" />
                                    Save transaction
                                </Button>

                                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    Transaksi berhasil ditambahkan.
                                </p>
                            </form>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
