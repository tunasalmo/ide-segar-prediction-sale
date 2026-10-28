<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ChartCanvas from '@/Components/ChartCanvas.vue';
import { rupiah, num, pct } from '@/lib/format';

const props = defineProps({
    product: Object,
    summary: Object,
    prediction: Object,
    history: Array,
    recentItems: Array,
    categories: Array,
});

// grafik riwayat bulanan (qty)
const historyData = computed(() => ({
    labels: props.history.map((h) => h.bulan),
    datasets: [
        {
            label: 'Qty terjual',
            data: props.history.map((h) => h.qty),
            backgroundColor: 'rgba(90,69,199,0.18)',
            borderColor: '#5A45C7',
            borderWidth: 1,
            borderRadius: 4,
            maxBarThickness: 36,
        },
    ],
}));

const historyOptions = {
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true },
    },
};

// nama bulan Indonesia — sama dengan format Carbon 'M Y' pada riwayat (history[].bulan)
const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// grafik prediksi per bulan: riwayat penjualan + proyeksi (agregasi deret harian) + garis tren
const predictionTrendData = computed(() => {
    const labelIndex = new Map();
    const labels = [];
    const riwayat = [];
    const prediksi = [];
    const tren = [];

    // riwayat datang terbaru duluan dari backend → balik jadi kronologis
    for (const h of [...props.history].reverse()) {
        labelIndex.set(h.bulan, labels.length);
        labels.push(h.bulan);
        riwayat.push(h.qty);
        prediksi.push(null);
        tren.push(h.qty);
    }

    // agregasi deret harian prediksi per bulan ('YYYY-MM-DD' -> qty)
    const sums = new Map();
    for (const [d, v] of Object.entries(props.prediction?.series ?? {})) {
        const key = d.slice(0, 7);
        sums.set(key, (sums.get(key) ?? 0) + v);
    }
    for (const [key, total] of [...sums.entries()].sort(([a], [b]) => a.localeCompare(b))) {
        const [y, m] = key.split('-');
        const label = `${BULAN[Number(m) - 1]} ${y}`;
        if (labelIndex.has(label)) {
            // bulan yang sama dengan bulan riwayat terakhir (periode prediksi lintas bulan)
            const i = labelIndex.get(label);
            prediksi[i] = total;
            tren[i] = riwayat[i] + total;
        } else {
            labelIndex.set(label, labels.length);
            labels.push(label);
            riwayat.push(null);
            prediksi.push(total);
            tren.push(total);
        }
    }

    return {
        labels,
        datasets: [
            {
                type: 'bar',
                label: 'Riwayat',
                data: riwayat,
                backgroundColor: 'rgba(90,69,199,0.18)',
                borderColor: '#5A45C7',
                borderWidth: 1,
                borderRadius: 4,
                maxBarThickness: 36,
            },
            {
                type: 'bar',
                label: 'Prediksi',
                data: prediksi,
                backgroundColor: 'rgba(46,155,214,0.22)',
                borderColor: '#2E9BD6',
                borderWidth: 1,
                borderRadius: 4,
                maxBarThickness: 36,
            },
            {
                type: 'line',
                label: 'Tren',
                data: tren,
                borderColor: '#1B1815',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [4, 3],
                tension: 0,
                pointRadius: 3,
                pointBackgroundColor: '#1B1815',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 1.5,
                spanGaps: false,
            },
        ],
    };
});

const predictionTrendOptions = {
    plugins: {
        legend: {
            labels: { boxWidth: 14, boxHeight: 2, usePointStyle: false, font: { size: 11 } },
        },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    if (ctx.parsed.y === null) return null;
                    return ` ${ctx.dataset.label}: ${num(ctx.parsed.y, 2)} ${props.product.unit}`;
                },
            },
        },
    },
    scales: {
        x: { ticks: { maxTicksLimit: 12, maxRotation: 0, autoSkip: true } },
        y: { beginAtZero: true, ticks: { precision: 0 } },
    },
};

// arah kecenderungan garis tren: naik / turun / stabil
const trendDirection = computed(() => {
    const pts = (predictionTrendData.value.datasets[2]?.data ?? []).filter((v) => v !== null);
    if (pts.length < 2) return null;
    const first = pts[0];
    const last = pts[pts.length - 1];
    if (last > first) return { arrow: '↗', text: 'cenderung naik', cls: 'text-ok' };
    if (last < first) return { arrow: '↘', text: 'cenderung turun', cls: 'text-bad' };
    return { arrow: '→', text: 'cenderung stabil', cls: 'text-ink-faint' };
});

const editOpen = ref(false);
const form = useForm({
    name: props.product.name,
    code: props.product.code,
    category_id: props.product.category_id,
    unit: props.product.unit,
    price: props.product.price,
    stock: props.product.stock ?? '',
    is_active: props.product.is_active,
});

function submitEdit() {
    form.put(`/produk/${props.product.id}`, {
        preserveScroll: true,
        onSuccess: () => (editOpen.value = false),
    });
}

function toggleActive() {
    if (props.product.is_active) {
        if (confirm(`Nonaktifkan ${props.product.name}?`)) router.delete(`/produk/${props.product.id}`, { preserveScroll: true });
    } else {
        router.post(`/produk/${props.product.id}/aktifkan`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- ringkasan produk -->
            <div class="panel flex flex-wrap items-start gap-5 p-6">
                <div class="flex h-14 w-14 items-center justify-center rounded-card border border-line bg-paper font-mono text-sm font-semibold text-ink-faint">
                    {{ product.code.slice(0, 3) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display text-xl font-bold tracking-tight">{{ product.name }}</h2>
                        <span class="rounded-full border border-line px-2 py-0.5 font-mono text-[10px] text-ink-faint">{{ product.code }}</span>
                        <span class="inline-flex items-center gap-1.5 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" :class="product.is_active ? 'bg-ok' : 'bg-ink-faint'"></span>
                            <span class="text-ink-soft">{{ product.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </span>
                    </div>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-soft">
                        <span class="h-2 w-2 rounded-[2px]" :style="{ backgroundColor: product.category_color }"></span>
                        {{ product.category }} · satuan {{ product.unit }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button class="btn-secondary" @click="editOpen = true">Ubah data</button>
                    <button class="btn-secondary" :class="product.is_active ? 'text-bad' : 'text-ok'" @click="toggleActive">
                        {{ product.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </div>
            </div>

            <!-- kartu metrik -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="panel p-5">
                    <p class="kicker">Harga satuan</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ rupiah(product.price) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">Total terjual (seluruh data)</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ num(summary.total_sold) }} {{ product.unit }}</p>
                    <p class="mt-1.5 font-mono text-[11px] text-ink-faint">{{ num(summary.orders_count) }} pesanan</p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">Pendapatan (seluruh data)</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ rupiah(summary.total_revenue) }}</p>
                </div>
            </div>

            <!-- prediksi -->
            <div v-if="prediction" class="panel p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <p class="kicker">Prediksi terbaru · {{ prediction.run_date }}</p>
                        <h3 class="mt-1 font-display text-base font-bold">
                            {{ num(prediction.predicted_qty) }} {{ product.unit }}
                            <span class="text-sm font-medium text-ink-faint">untuk {{ prediction.period_start }} – {{ prediction.period_end }}</span>
                        </h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="kicker">Periode pembanding</p>
                            <p class="num text-sm font-semibold">{{ num(prediction.actual_qty) }} {{ product.unit }}</p>
                        </div>
                        <div class="text-right">
                            <p class="kicker">Pertumbuhan</p>
                            <p class="num text-lg font-semibold" :class="(prediction.growth_pct ?? 0) >= 0 ? 'text-ok' : 'text-bad'">
                                {{ pct(prediction.growth_pct) }}
                            </p>
                        </div>
                        <StatusBadge :value="prediction.status_label" />
                    </div>
                </div>

                <!-- grafik prediksi per bulan: riwayat + proyeksi + tren -->
                <div class="mt-5 border-t border-line/60 pt-4">
                    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                        <div>
                            <p class="kicker">Grafik prediksi per bulan</p>
                            <h4 class="mt-1 font-display text-sm font-bold">Riwayat & proyeksi qty bulanan — {{ prediction.period_start }} s.d. {{ prediction.period_end }}</h4>
                        </div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span
                                v-if="trendDirection"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 font-mono text-[11px] font-semibold"
                                :class="[trendDirection.cls, 'border-line']"
                            >
                                {{ trendDirection.arrow }} {{ trendDirection.text }}
                            </span>
                            <span class="font-mono text-[10px] text-ink-faint">model {{ prediction.model_version ?? '—' }} · {{ Object.keys(prediction.series ?? {}).length }} hari</span>
                        </div>
                    </div>
                    <div v-if="Object.keys(prediction.series ?? {}).length" class="h-56">
                        <ChartCanvas type="bar" :data="predictionTrendData" :options="predictionTrendOptions" />
                    </div>
                    <p v-else class="font-mono text-[11px] leading-relaxed text-ink-faint">
                        Deret prediksi tidak tersedia untuk produk ini pada run terakhir.
                    </p>
                    <p class="mt-2 font-mono text-[10px] text-ink-faint">
                        Nilai prediksi per bulan dijumlahkan dari deret harian model ({{ prediction.model_version ?? 'linreg-weekly-v1' }}) untuk periode {{ prediction.period_start }} – {{ prediction.period_end }}. Garis tren menghubungkan riwayat dengan proyeksi agar arah naik/turun terlihat.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <!-- riwayat bulanan -->
                <div class="panel p-5">
                    <p class="kicker">Riwayat penjualan</p>
                    <h3 class="mt-1 font-display text-base font-bold">Per bulan</h3>
                    <div class="mt-4 h-56">
                        <ChartCanvas type="bar" :data="historyData" :options="historyOptions" />
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Pesanan</th>
                                    <th class="text-right">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="h in history" :key="h.bulan">
                                    <td class="font-mono text-xs">{{ h.bulan }}</td>
                                    <td class="num text-right">{{ num(h.qty) }}</td>
                                    <td class="num text-right text-ink-soft">{{ num(h.pesanan) }}</td>
                                    <td class="num text-right">{{ rupiah(h.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- item terakhir -->
                <div class="panel">
                    <div class="border-b border-line px-5 py-4">
                        <p class="kicker">Transaksi terakhir</p>
                        <h3 class="mt-1 font-display text-base font-bold">Item penjualan</h3>
                    </div>
                    <div v-if="recentItems.length" class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Pelanggan</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="i in recentItems" :key="i.invoice_no">
                                    <td class="num text-xs text-accent">{{ i.invoice_no }}</td>
                                    <td class="text-xs text-ink-soft">{{ i.customer }}</td>
                                    <td class="num text-right text-xs">{{ num(i.qty) }} {{ i.unit }}</td>
                                    <td class="num text-right">{{ rupiah(i.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else title="Belum ada transaksi" body="Item penjualan produk ini akan tampil di sini." />
                </div>
            </div>

            <Link href="/produk" class="font-mono text-[11px] text-accent hover:text-accent-dark">← Kembali ke daftar produk</Link>
        </div>

        <!-- modal edit -->
        <Modal :show="editOpen" @close="editOpen = false" max-width="lg">
            <form @submit.prevent="submitEdit" class="p-6">
                <h2 class="font-display text-lg font-bold">Ubah produk</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="e-name" value="Nama produk" />
                        <TextInput id="e-name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e-code" value="Kode" />
                        <TextInput id="e-code" v-model="form.code" class="mt-1 block w-full font-mono uppercase" required />
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e-cat" value="Kategori" />
                        <select id="e-cat" v-model="form.category_id" class="input mt-1 block w-full">
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <InputError :message="form.errors.category_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e-unit" value="Satuan" />
                        <TextInput id="e-unit" v-model="form.unit" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel for="e-price" value="Harga (Rp)" />
                        <TextInput id="e-price" v-model="form.price" type="number" min="0" step="500" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.price" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e-stock" value="Stok (opsional)" />
                        <TextInput id="e-stock" v-model="form.stock" type="number" min="0" class="mt-1 block w-full" placeholder="kosong bila tidak dikelola" />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-line text-accent focus:ring-accent" />
                            Produk aktif
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="editOpen = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
