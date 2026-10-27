<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ChartCanvas from '@/Components/ChartCanvas.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { rupiah, num, pct } from '@/lib/format';

const props = defineProps({
    metrics: Object,
    trend: Object,
    categoryShare: Array,
    recentOrders: Array,
    recommendations: Array,
    prediction: Object,
});

const shareTotal = computed(() => props.categoryShare.reduce((s, c) => s + c.total, 0));

const donutData = computed(() => ({
    labels: props.categoryShare.map((c) => c.name),
    datasets: [
        {
            data: props.categoryShare.map((c) => c.total),
            backgroundColor: props.categoryShare.map((c) => c.color),
            borderColor: '#FFFFFF',
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const donutOptions = {
    cutout: '68%',
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    const t = props.categoryShare[ctx.dataIndex]?.total ?? 0;
                    const p = shareTotal.value ? (t / shareTotal.value) * 100 : 0;
                    return ` ${ctx.label}: ${rupiah(t)} (${p.toLocaleString('id-ID', { maximumFractionDigits: 1 })}%)`;
                },
            },
        },
    },
};

const trendOptions = {
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => ` ${rupiah(ctx.parsed.y)}`,
            },
        },
    },
    scales: {
        y: {
            ticks: {
                callback: (v) => (v >= 1_000_000 ? `${(v / 1_000_000).toLocaleString('id-ID')} jt` : v),
            },
        },
    },
    elements: { line: { tension: 0.25, borderWidth: 1.5 } },
};

function sharePct(total) {
    return shareTotal.value
        ? ((total / shareTotal.value) * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 })
        : '0';
}
</script>

<template>
    <AppLayout>
        <div class="space-y-5">
            <!-- strip prediksi -->
            <div v-if="prediction" class="panel flex flex-wrap items-center gap-x-8 gap-y-3 px-5 py-4">
                <div>
                    <p class="kicker">Prediksi terbaru</p>
                    <p class="mt-1 font-display text-lg font-bold">
                        {{ rupiah(prediction.predicted_revenue_total) }}
                        <span class="text-sm font-medium text-ink-faint">/ 90 hari</span>
                    </p>
                </div>
                <div class="h-8 w-px bg-line"></div>
                <div>
                    <p class="kicker">Akurasi evaluasi</p>
                    <p class="mt-1 num text-lg font-semibold text-accent">{{ num(prediction.accuracy_pct, 1) }}%</p>
                </div>
                <div class="h-8 w-px bg-line"></div>
                <div>
                    <p class="kicker">Diperbarui</p>
                    <p class="mt-1 num text-sm text-ink-soft">{{ prediction.updated_at }}</p>
                </div>
                <div class="ml-auto">
                    <Link href="/prediksi" class="btn-secondary">Buka halaman prediksi →</Link>
                </div>
            </div>

            <!-- kartu metrik -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="kicker">Pendapatan · 30 hari</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ rupiah(metrics.revenue_30d) }}</p>
                    <p class="mt-1.5 font-mono text-[11px]" :class="(metrics.revenue_delta ?? 0) >= 0 ? 'text-ok' : 'text-bad'">
                        {{ pct(metrics.revenue_delta) }} vs periode sebelumnya
                    </p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">Pesanan · 30 hari</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ num(metrics.orders_30d) }}</p>
                    <p class="mt-1.5 font-mono text-[11px]" :class="(metrics.orders_delta ?? 0) >= 0 ? 'text-ok' : 'text-bad'">
                        {{ pct(metrics.orders_delta) }} vs periode sebelumnya
                    </p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">Pelanggan · total</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ num(metrics.customers) }}</p>
                    <p class="mt-1.5 font-mono text-[11px] text-ink-faint">{{ num(metrics.products) }} produk aktif</p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">
                        Invoice belum lunas
                        <span class="ml-1 cursor-help text-[10px]" title="Total nilai invoice berstatus Belum Bayar atau DP (nilai penuh). Sisa tagihan aktual per invoice tidak tersimpan dalam backup data.">ⓘ</span>
                    </p>
                    <p class="mt-2 num text-2xl font-semibold text-warn">{{ rupiah(metrics.unpaid) }}</p>
                    <p class="mt-1.5 font-mono text-[11px] text-ink-faint">
                        {{ num(metrics.attention) }} pesanan menunggu konfirmasi
                    </p>
                </div>
            </div>

            <!-- grafik + donat -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="panel p-5 xl:col-span-2">
                    <div class="mb-4 flex items-baseline justify-between">
                        <div>
                            <p class="kicker">Tren pendapatan harian</p>
                            <h2 class="mt-1 font-display text-base font-bold">30 hari terakhir</h2>
                        </div>
                        <Link href="/laporan" class="font-mono text-[11px] text-accent hover:text-accent-dark">Laporan →</Link>
                    </div>
                    <div class="h-64">
                        <ChartCanvas
                            type="line"
                            :data="{
                                labels: trend.labels,
                                datasets: [
                                    {
                                        data: trend.values,
                                        borderColor: '#5A45C7',
                                        backgroundColor: 'rgba(90,69,199,0.06)',
                                        fill: true,
                                        pointRadius: 0,
                                        pointHitRadius: 8,
                                    },
                                ],
                            }"
                            :options="trendOptions"
                        />
                    </div>
                </div>

                <div class="panel p-5">
                    <div class="mb-2">
                        <p class="kicker">Distribusi kategori</p>
                        <h2 class="mt-1 font-display text-base font-bold">Kontribusi pendapatan</h2>
                    </div>
                    <div class="h-40">
                        <ChartCanvas type="doughnut" :data="donutData" :options="donutOptions" />
                    </div>
                    <ul class="mt-4 space-y-1.5">
                        <li v-for="c in categoryShare.slice(0, 6)" :key="c.name" class="flex items-center gap-2 text-xs">
                            <span class="h-2 w-2 rounded-[2px]" :style="{ backgroundColor: c.color }"></span>
                            <span class="min-w-0 flex-1 truncate text-ink-soft">{{ c.name }}</span>
                            <span class="num font-medium text-ink">{{ sharePct(c.total) }}%</span>
                        </li>
                        <li v-if="categoryShare.length > 6" class="pt-1 font-mono text-[10px] text-ink-faint">
                            + {{ categoryShare.length - 6 }} kategori lain
                        </li>
                    </ul>
                    <p class="mt-3 border-t border-line/60 pt-2 font-mono text-[10px] text-ink-faint">
                        Definisi: pendapatan aktual dari nilai invoice, seluruh periode data.
                    </p>
                </div>
            </div>

            <!-- pesanan terbaru + rekomendasi -->
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="panel xl:col-span-2">
                    <div class="flex items-baseline justify-between border-b border-line px-5 py-4">
                        <h2 class="font-display text-base font-bold">Pesanan terbaru</h2>
                        <Link href="/pesanan" class="font-mono text-[11px] text-accent hover:text-accent-dark">Semua pesanan →</Link>
                    </div>
                    <div v-if="recentOrders.length" class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th class="text-right">Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="o in recentOrders" :key="o.id">
                                    <td>
                                        <Link :href="`/pesanan/${o.id}`" class="num text-xs font-medium text-accent hover:text-accent-dark">
                                            {{ o.invoice_no }}
                                        </Link>
                                    </td>
                                    <td class="text-ink-soft">{{ o.customer }}</td>
                                    <td class="num text-xs text-ink-soft">{{ o.order_date }}</td>
                                    <td class="num text-right">{{ rupiah(o.total) }}</td>
                                    <td><StatusBadge :value="o.status" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else title="Belum ada pesanan" body="Pesanan akan tampil di sini." />
                </div>

                <div class="panel">
                    <div class="flex items-baseline justify-between border-b border-line px-5 py-4">
                        <h2 class="font-display text-base font-bold">Rekomendasi</h2>
                        <Link href="/prediksi" class="font-mono text-[11px] text-accent hover:text-accent-dark">Prediksi →</Link>
                    </div>
                    <ul v-if="recommendations.length" class="divide-y divide-line/60">
                        <li v-for="r in recommendations" :key="r.id" class="flex items-center gap-3 px-5 py-3">
                            <span class="num font-mono text-[11px] text-ink-faint">{{ r.code }}</span>
                            <span class="min-w-0 flex-1">
                                <Link :href="`/produk/${r.id}`" class="block truncate text-[13px] font-medium hover:text-accent">
                                    {{ r.name }}
                                </Link>
                                <span class="font-mono text-[10px] text-ink-faint">
                                    {{ num(r.predicted_qty) }} {{ r.unit }} diprediksi
                                </span>
                            </span>
                            <span class="num text-xs font-semibold" :class="r.growth_pct >= 0 ? 'text-ok' : 'text-bad'">
                                {{ pct(r.growth_pct) }}
                            </span>
                        </li>
                    </ul>
                    <EmptyState
                        v-else
                        title="Belum ada rekomendasi"
                        body="Jalankan prediksi untuk melihat produk berpotensi tinggi."
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
