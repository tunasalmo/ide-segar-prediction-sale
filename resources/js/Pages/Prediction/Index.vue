<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ChartCanvas from '@/Components/ChartCanvas.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { rupiah, num, pct } from '@/lib/format';

const props = defineProps({
    run: Object,
    evaluation: Object,
    cards: Object,
    insight: Object,
    rangeOptions: Object,
    charts: Object,
    categoryShare: Array,
    shareTotal: Number,
    topProducts: Array,
    allTop: Number,
    hint: String,
});

const range = ref('30');
const showAll = ref(false);
const recoOpen = ref(false);

const chart = computed(() => props.charts?.['projection' + range.value]);
const visibleProducts = computed(() => (showAll.value ? props.topProducts : props.topProducts.slice(0, 10)));

const projectionData = computed(() => ({
    labels: chart.value?.labels ?? [],
    datasets: [
        {
            label: 'Aktual',
            data: chart.value?.actual ?? [],
            borderColor: '#5A45C7',
            backgroundColor: 'rgba(90,69,199,0.05)',
            fill: true,
            pointRadius: 0,
            pointHitRadius: 8,
            tension: 0.2,
            spanGaps: false,
        },
        {
            label: 'Prediksi',
            data: chart.value?.predicted ?? [],
            borderColor: '#1B1815',
            borderDash: [5, 4],
            backgroundColor: 'transparent',
            pointRadius: 0,
            pointHitRadius: 8,
            tension: 0.2,
            spanGaps: false,
        },
    ],
}));

const projectionOptions = {
    plugins: {
        legend: {
            labels: { boxWidth: 14, boxHeight: 2, usePointStyle: false, font: { size: 11 } },
        },
        tooltip: {
            callbacks: {
                label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y === null ? '—' : rupiah(ctx.parsed.y)}`,
            },
        },
    },
    scales: {
        x: {
            ticks: {
                maxTicksLimit: 10,
                maxRotation: 0,
                autoSkip: true,
            },
        },
        y: {
            ticks: {
                callback: (v) => (v >= 1_000_000 ? `${(v / 1_000_000).toLocaleString('id-ID')} jt` : v),
            },
        },
    },
};

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
    cutout: '70%',
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    const t = props.categoryShare[ctx.dataIndex]?.total ?? 0;
                    const p = props.shareTotal ? (t / props.shareTotal) * 100 : 0;
                    return ` ${ctx.label}: ${rupiah(t)} (${p.toLocaleString('id-ID', { maximumFractionDigits: 1 })}%)`;
                },
            },
        },
    },
};

function sharePct(total) {
    return props.shareTotal
        ? ((total / props.shareTotal) * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 })
        : '0';
}

function actualPeriodLabel() {
    if (!props.run) return '';
    const end = props.run.data_end;
    const days = Number(range.value);
    // periode pembanding sama panjang sebelum akhir data
    return `periode pembanding`;
}
</script>

<template>
    <AppLayout>
        <template v-if="run">
            <div class="space-y-5">
                <!-- ===== A. Banner insight mingguan ===== -->
                <section
                    v-if="insight"
                    class="halftone relative overflow-hidden rounded-card bg-accent p-6 text-white sm:p-7"
                    aria-label="Insight mingguan"
                >
                    <div class="reg-mark">
                        <span class="bg-cmyk-cyan"></span>
                        <span class="bg-cmyk-magenta"></span>
                        <span class="bg-cmyk-yellow"></span>
                        <span class="bg-white/80"></span>
                    </div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.24em] text-accent-line">Insight mingguan</p>
                    <h2 class="mt-2 max-w-2xl font-display text-xl font-bold leading-snug tracking-tight sm:text-2xl">
                        {{ insight.title }}
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/85">{{ insight.body }}</p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-accent-dark transition-colors hover:bg-accent-soft" @click="recoOpen = true">
                            Lihat rekomendasi
                        </button>
                        <Link :href="`/produk/${insight.product_id}`" class="text-sm font-medium text-white/80 underline decoration-accent-line underline-offset-4 hover:text-white">
                            Detail produk {{ insight.product_code }}
                        </Link>
                    </div>
                </section>

                <!-- ===== B. Kartu ringkasan prediksi ===== -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="panel p-5">
                        <p class="kicker">Prediksi pendapatan</p>
                        <p class="mt-2 num text-2xl font-semibold">{{ rupiah(run.predicted_revenue_total) }}</p>
                        <p class="mt-1.5 font-mono text-[11px] text-ink-faint">{{ run.period_start }} – {{ run.period_end }}</p>
                    </div>
                    <div class="panel p-5">
                        <p class="kicker">Prediksi pesanan</p>
                        <p class="mt-2 num text-2xl font-semibold">{{ num(run.predicted_orders_total) }}</p>
                        <p class="mt-1.5 font-mono text-[11px] text-ink-faint">estimasi jumlah pesanan, horizon {{ run.horizon_days }} hari</p>
                    </div>
                    <div class="panel p-5">
                        <p class="kicker">
                            Akurasi model
                            <span class="ml-1 inline-block cursor-help text-[10px] text-ink-faint" title="Dihitung dari 100 − MAPE pada 4 minggu terakhir (hold-out). MAPE dievaluasi di level total mingguan toko karena permintaan per produk intermittent. Lihat detail di tooltip / kartu ini.">ⓘ</span>
                        </p>
                        <p class="mt-2 num text-2xl font-semibold text-accent">{{ num(run.accuracy_pct, 1) }}%</p>
                        <p class="mt-1.5 font-mono text-[11px] text-ink-faint">
                            100 − MAPE {{ num(evaluation.mape, 1) }}% · hold-out 4 minggu
                        </p>
                    </div>
                    <div class="panel p-5">
                        <p class="kicker">Produk bertumbuh</p>
                        <p class="mt-2 num text-2xl font-semibold">{{ num(cards.growing) }}</p>
                        <p class="mt-1.5 font-mono text-[11px]" :class="cards.priority > 0 ? 'text-bad' : 'text-ink-faint'">
                            {{ num(cards.priority) }} perlu prioritas stok
                        </p>
                    </div>
                </div>

                <!-- ===== C. Grafik proyeksi + D. distribusi kategori ===== -->
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <div class="panel p-5 xl:col-span-2">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="kicker">Proyeksi penjualan</p>
                                <h2 class="mt-1 font-display text-base font-bold">Aktual vs prediksi (pendapatan harian)</h2>
                            </div>
                            <div class="flex rounded-lg border border-line bg-paper p-0.5" role="tablist" aria-label="Pilih periode grafik">
                                <button
                                    v-for="(label, key) in rangeOptions"
                                    :key="key"
                                    role="tab"
                                    :aria-selected="range === key"
                                    class="rounded-md px-3 py-1.5 font-mono text-[11px] font-medium transition-colors"
                                    :class="range === key ? 'bg-accent text-white' : 'text-ink-soft hover:text-ink'"
                                    @click="range = key"
                                >
                                    {{ label }}
                                </button>
                            </div>
                        </div>
                        <p class="mb-3 font-mono text-[10px] text-ink-faint">
                            Aktual: {{ run.data_end }} · prediksi {{ run.period_start }} – {{ run.period_end }} · garis prediksi putus-putus (estimasi)
                        </p>
                        <div class="h-72">
                            <ChartCanvas type="line" :data="projectionData" :options="projectionOptions" />
                        </div>
                    </div>

                    <div class="panel p-5">
                        <div class="mb-2">
                            <p class="kicker">Distribusi kategori</p>
                            <h2 class="mt-1 font-display text-base font-bold">Kontribusi pendapatan</h2>
                        </div>
                        <div class="relative h-44">
                            <ChartCanvas type="doughnut" :data="donutData" :options="donutOptions" />
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <p class="num text-lg font-semibold text-ink">{{ shareTotal ? '100%' : '—' }}</p>
                                <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-ink-faint">kontribusi</p>
                            </div>
                        </div>
                        <ul class="mt-4 space-y-1.5">
                            <li v-for="c in categoryShare" :key="c.name" class="flex items-center gap-2 text-xs">
                                <span class="h-2 w-2 rounded-[2px]" :style="{ backgroundColor: c.color }"></span>
                                <span class="min-w-0 flex-1 truncate text-ink-soft">{{ c.name }}</span>
                                <span class="num font-medium text-ink">{{ sharePct(c.total) }}%</span>
                            </li>
                        </ul>
                        <p class="mt-3 border-t border-line/60 pt-2 font-mono text-[10px] text-ink-faint">
                            Definisi: kontribusi pendapatan aktual (nilai invoice), seluruh periode data.
                        </p>
                    </div>
                </div>

                <!-- ===== E. Produk dengan potensi tertinggi ===== -->
                <div class="panel">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-line px-5 py-4">
                        <div>
                            <p class="kicker">Rekomendasi model</p>
                            <h2 class="mt-1 font-display text-base font-bold">Produk dengan potensi tertinggi</h2>
                        </div>
                        <button v-if="allTop > 10" class="font-mono text-[11px] text-accent hover:text-accent-dark" @click="showAll = !showAll">
                            {{ showAll ? 'Ringkas ↑' : `Lihat semua (${num(allTop)}) ↓` }}
                        </button>
                    </div>
                    <div v-if="visibleProducts.length" class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-right">Terjual · pembanding</th>
                                    <th class="text-right">Prediksi</th>
                                    <th class="text-right">Pertumbuhan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in visibleProducts" :key="p.id">
                                    <td>
                                        <Link :href="`/produk/${p.id}`" class="group flex items-center gap-2.5">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-md border border-line bg-paper font-mono text-[9px] font-semibold text-ink-faint group-hover:border-accent group-hover:text-accent">
                                                {{ p.code.slice(0, 3) }}
                                            </span>
                                            <span>
                                                <span class="block text-[13px] font-medium group-hover:text-accent">{{ p.name }}</span>
                                                <span class="block font-mono text-[10px] text-ink-faint">{{ p.code }}</span>
                                            </span>
                                        </Link>
                                    </td>
                                    <td class="text-ink-soft">{{ p.category }}</td>
                                    <td class="num text-right text-ink-soft">{{ num(p.actual_qty) }} {{ p.unit }}</td>
                                    <td class="num text-right font-semibold">{{ num(p.predicted_qty) }} {{ p.unit }}</td>
                                    <td class="num text-right font-semibold" :class="(p.growth_pct ?? 0) >= 0 ? 'text-ok' : 'text-bad'">
                                        {{ pct(p.growth_pct) }}
                                    </td>
                                    <td><StatusBadge :value="p.status_label" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else title="Belum ada produk berpotensi" body="Hasil prediksi akan tampil setelah model dijalankan." />
                    <p class="border-t border-line/60 px-5 py-2.5 font-mono text-[10px] text-ink-faint">
                        Urutan: skor potensi (prediksi qty × pertumbuhan). Label prioritas: pertumbuhan ≥ 20% & prediksi ≥ 10 qty = prioritas stok; ≥ 5% = pantau.
                    </p>
                </div>

                <!-- metadata run -->
                <p class="font-mono text-[10px] text-ink-faint">
                    Model {{ run.model_version }} · data {{ run.data_start }} – {{ run.data_end }} · diperbarui {{ run.updated_at }} · MAE {{ num(evaluation.mae, 2) }} / RMSE {{ num(evaluation.rmse, 2) }} (qty per minggu, hold-out)
                </p>
            </div>

            <!-- ===== modal rekomendasi ===== -->
            <Teleport to="body">
                <div v-if="recoOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Rincian rekomendasi">
                    <div class="absolute inset-0 bg-ink/50" @click="recoOpen = false"></div>
                    <div class="relative w-full max-w-lg overflow-hidden rounded-card border border-line bg-surface shadow-lift">
                        <div class="halftone bg-accent px-6 py-5 text-white">
                            <p class="font-mono text-[10px] uppercase tracking-[0.24em] text-accent-line">Rekomendasi mingguan</p>
                            <h3 class="mt-1.5 font-display text-lg font-bold">{{ insight.title }}</h3>
                        </div>
                        <div class="space-y-4 px-6 py-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="kicker">Produk</p>
                                    <p class="mt-1 text-sm font-medium">{{ insight.product_name }}</p>
                                    <p class="num font-mono text-[11px] text-ink-faint">{{ insight.product_code }}</p>
                                </div>
                                <div>
                                    <p class="kicker">Perkiraan perubahan</p>
                                    <p class="mt-1 num text-lg font-semibold text-ok">{{ pct(insight.growth_pct) }}</p>
                                    <p class="font-mono text-[11px] text-ink-faint">vs periode pembanding</p>
                                </div>
                            </div>
                            <div class="rounded-lg border border-line bg-paper/60 p-3.5 text-xs leading-relaxed text-ink-soft">
                                <p class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-faint">Dasar rekomendasi</p>
                                Tren historis kategori {{ insight.product_name.split(' ').slice(-1) }} — {{ insight.body }}
                            </div>
                            <div class="rounded-lg border border-line p-3.5 text-xs leading-relaxed text-ink-soft">
                                <p class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-faint">Saran operasional</p>
                                Periksa ketersediaan bahan baku dan jadwal produksi sebelum menerima pesanan tambahan. Prediksi adalah estimasi, bukan jaminan hasil penjualan.
                            </div>
                            <div class="flex justify-end gap-2">
                                <button class="btn-secondary" @click="recoOpen = false">Tutup</button>
                                <Link :href="`/produk/${insight.product_id}`" class="btn-primary" @click="recoOpen = false">Buka detail produk</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </template>

        <!-- state kosong: belum ada run -->
        <template v-else>
            <div class="panel">
                <EmptyState title="Prediksi belum dibuat" :body="hint">
                    <code class="mt-2 rounded-md border border-line bg-paper px-2.5 py-1 font-mono text-xs text-ink">
                        php artisan predictions:run
                    </code>
                </EmptyState>
            </div>
        </template>
    </AppLayout>
</template>
