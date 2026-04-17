<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ArrowUpRight, Landmark, Wallet } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface ChartDataset {
    borderColor: string;
    data: number[];
    fill: boolean;
    label: string;
    tension: number;
}

interface ChartConfiguration {
    data: {
        datasets: ChartDataset[];
        labels: string[];
    };
    options: Record<string, unknown>;
    type: string;
}

interface ChartJsInstance {
    destroy: () => void;
}

interface ChartJsConstructor {
    new (context: HTMLCanvasElement, config: ChartConfiguration): ChartJsInstance;
}

declare global {
    interface Window {
        Chart?: ChartJsConstructor;
    }
}

interface Totals {
    cash_balance: number;
    bank_balance: number;
    net_movement: number;
}

interface ChartPoint {
    label: string;
    debit: number;
    credit: number;
}

const props = defineProps<{
    totals: Totals;
    chart: ChartPoint[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
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

const chartMaxValue = computed(() => {
    const peak = Math.max(...props.chart.flatMap((point) => [point.debit, point.credit]), 0);

    return peak === 0 ? 1 : peak;
});

const chartSummary = computed(() => {
    return [
        {
            label: 'Credit',
            value: props.chart.reduce((total, point) => total + point.credit, 0),
            tone: 'bg-emerald-500',
        },
        {
            label: 'Debit',
            value: props.chart.reduce((total, point) => total + point.debit, 0),
            tone: 'bg-rose-500',
        },
    ];
});

const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chartInstance: ChartJsInstance | null = null;

const loadChartJs = async (): Promise<ChartJsConstructor> => {
    if (window.Chart) {
        return window.Chart;
    }

    await new Promise<void>((resolve, reject) => {
        const existingScript = document.querySelector<HTMLScriptElement>('script[data-chartjs-cdn="true"]');

        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Failed to load Chart.js')), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js';
        script.async = true;
        script.dataset.chartjsCdn = 'true';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Chart.js'));
        document.head.appendChild(script);
    });

    if (!window.Chart) {
        throw new Error('Chart.js is unavailable after loading the CDN script.');
    }

    return window.Chart;
};

const renderChart = async (): Promise<void> => {
    if (!chartCanvas.value) {
        return;
    }

    const Chart = await loadChartJs();

    chartInstance?.destroy();
    chartInstance = new Chart(chartCanvas.value, {
        type: 'line',
        data: {
            labels: props.chart.map((point) => point.label),
            datasets: [
                {
                    label: 'Credit',
                    data: props.chart.map((point) => point.credit),
                    borderColor: '#10b981',
                    fill: false,
                    tension: 0.35,
                },
                {
                    label: 'Debit',
                    data: props.chart.map((point) => point.debit),
                    borderColor: '#f43f5e',
                    fill: false,
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label(context: { dataset: { label: string }; parsed: { y: number } }) {
                            return `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#64748b',
                    },
                    border: {
                        display: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#64748b',
                        callback(value: string | number) {
                            return formatCurrency(Number(value));
                        },
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.18)',
                    },
                    border: {
                        display: false,
                    },
                },
            },
            elements: {
                line: {
                    borderWidth: 3,
                },
                point: {
                    radius: 4,
                    hoverRadius: 6,
                    backgroundColor: '#ffffff',
                    borderWidth: 2,
                },
            },
        },
    });
};

onMounted(() => {
    void renderChart();
});

watch(
    () => props.chart,
    () => {
        void renderChart();
    },
    { deep: true },
);

onBeforeUnmount(() => {
    chartInstance?.destroy();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <Card
                    v-for="card in summaryCards"
                    :key="card.title"
                    class="rounded-3xl border border-sidebar-border/70 bg-white/80 shadow-sm backdrop-blur dark:bg-background"
                >
                    <CardContent class="p-4">
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
                    </CardContent>
                </Card>
            </div>

            <div
                class="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background dark:border-sidebar-border md:min-h-min"
            >
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.08),_transparent_28%)]" />
                <div class="relative flex h-full flex-col gap-6 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-2xl">
                            <h2 class="text-xl font-semibold text-foreground">Grafik debit dan credit</h2>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">
                                Ringkasan tren transaksi debit dan credit harian untuk tujuh hari terakhir berdasarkan tanggal transaksi.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="item in chartSummary"
                                :key="item.label"
                                class="rounded-2xl border border-sidebar-border/70 bg-muted/30 px-4 py-3"
                            >
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                    <span :class="['size-2 rounded-full', item.tone]" />
                                    {{ item.label }}
                                </div>
                                <p class="mt-2 text-lg font-semibold text-foreground">{{ formatCurrency(item.value) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid flex-1 gap-6">
                        <div class="rounded-[1.5rem] border border-sidebar-border/70 bg-white/70 p-4 shadow-sm dark:bg-background">
                            <div class="flex h-full flex-col">
                                <div class="flex items-center justify-between border-b border-sidebar-border/70 pb-3">
                                    <div>
                                        <p class="text-sm font-semibold text-foreground">Debit vs Credit</p>
                                        <p class="text-xs text-muted-foreground">Visualisasi harian 7 hari terakhir</p>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs font-medium text-muted-foreground">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="size-2 rounded-full bg-emerald-500" />
                                            Credit
                                        </span>
                                        <span class="inline-flex items-center gap-2">
                                            <span class="size-2 rounded-full bg-rose-500" />
                                            Debit
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4 h-[360px]">
                                    <canvas ref="chartCanvas" class="h-full w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div
                                v-for="point in props.chart"
                                :key="point.label"
                                class="rounded-[1.5rem] border border-sidebar-border/70 bg-white/70 p-4 shadow-sm dark:bg-background"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{{ point.label }}</p>
                                <div class="mt-4 space-y-3">
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-sm">
                                            <span class="text-muted-foreground">Credit</span>
                                            <span class="font-medium text-emerald-600">{{ formatCurrency(point.credit) }}</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-emerald-500/10">
                                            <div
                                                class="h-2 rounded-full bg-emerald-500"
                                                :style="{ width: `${(point.credit / chartMaxValue) * 100}%` }"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-sm">
                                            <span class="text-muted-foreground">Debit</span>
                                            <span class="font-medium text-rose-600">{{ formatCurrency(point.debit) }}</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-rose-500/10">
                                            <div class="h-2 rounded-full bg-rose-500" :style="{ width: `${(point.debit / chartMaxValue) * 100}%` }" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
