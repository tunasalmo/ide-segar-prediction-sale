<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ChartCanvas from '@/Components/ChartCanvas.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { rupiah, num } from '@/lib/format';

const props = defineProps({
    filters: Object,
    categories: Array,
    summary: Object,
    rows: Array,
    daily: Object,
    generated_at: String,
});

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const category = ref(props.filters.category || '');
const groupBy = ref(props.filters.group_by || 'product');

let timer = null;
function apply() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/laporan', { from: from.value || undefined, to: to.value || undefined, category: category.value || undefined, group_by: groupBy.value }, { preserveState: true, replace: true });
    }, 250);
}
watch([from, to, category, groupBy], apply);

const exportUrl = computed(() => {
    const p = new URLSearchParams({ from: from.value, to: to.value });
    if (category.value) p.set('category', category.value);
    p.set('group_by', groupBy.value);
    return `/laporan/export?${p.toString()}`;
});

const trendData = computed(() => ({
    labels: props.daily.labels,
    datasets: [
        {
            label: 'Pendapatan harian',
            data: props.daily.values,
            backgroundColor: 'rgba(90,69,199,0.16)',
            borderColor: '#5A45C7',
            borderWidth: 1.5,
            pointRadius: 0,
            tension: 0.2,
        },
    ],
}));

const trendOptions = {
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => ` ${rupiah(ctx.parsed.y)}` } },
    },
    scales: { y: { ticks: { callback: (v) => (v >= 1_000_000 ? `${(v / 1_000_000).toLocaleString('id-ID')} jt` : v) } } },
};
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- filter -->
            <div class="panel flex flex-wrap items-end gap-3 p-4">
                <label class="flex flex-col gap-1 text-xs font-medium text-ink-soft">
                    Dari
                    <input v-model="from" type="date" class="input" />
                </label>
                <label class="flex flex-col gap-1 text-xs font-medium text-ink-soft">
                    Sampai
                    <input v-model="to" type="date" class="input" />
                </label>
                <label class="flex flex-col gap-1 text-xs font-medium text-ink-soft">
                    Kategori
                    <select v-model="category" class="input w-auto">
                        <option value="">Semua</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-xs font-medium text-ink-soft">
                    Kelompok
                    <select v-model="groupBy" class="input w-auto">
                        <option value="product">Per produk</option>
                        <option value="category">Per kategori</option>
                    </select>
                </label>
                <a :href="exportUrl" class="btn-secondary ml-auto" download>⭳ Ekspor CSV</a>
            </div>

            <!-- ringkasan -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="panel p-5">
                    <p class="kicker">Pendapatan</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ rupiah(summary.revenue) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">Jumlah pesanan</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ num(summary.orders) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">Qty terjual</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ num(summary.qty) }}</p>
                    <p class="mt-1.5 font-mono text-[11px] text-ink-faint">dibuat {{ generated_at }}</p>
                </div>
            </div>

            <!-- tren -->
            <div class="panel p-5">
                <p class="kicker">Tren harian pada rentang</p>
                <h2 class="mt-1 font-display text-base font-bold">Pendapatan per hari</h2>
                <div class="mt-4 h-52">
                    <ChartCanvas type="line" :data="trendData" :options="trendOptions" />
                </div>
            </div>

            <!-- tabel rincian -->
            <div class="panel overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h3 class="font-display text-base font-bold">{{ groupBy === 'category' ? 'Rincian per kategori' : 'Rincian per produk' }}</h3>
                </div>
                <div v-if="rows.length" class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>{{ groupBy === 'category' ? 'Kategori' : 'Produk' }}</th>
                                <th>Kategori</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Pesanan</th>
                                <th class="text-right">Pendapatan</th>
                                <th class="text-right">Pangsa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, i) in rows" :key="r.name">
                                <td class="text-[13px] font-medium">{{ r.name }}</td>
                                <td class="text-xs text-ink-soft">{{ r.category }}</td>
                                <td class="num text-right">{{ num(r.qty) }}</td>
                                <td class="num text-right text-ink-soft">{{ num(r.orders) }}</td>
                                <td class="num text-right font-semibold">{{ rupiah(r.total) }}</td>
                                <td class="num text-right text-ink-soft">
                                    {{ summary.revenue ? ((r.total / summary.revenue) * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 }) : 0 }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Tidak ada data pada rentang ini" body="Perluas rentang tanggal atau hapus filter kategori." />
                <p class="border-t border-line/60 px-5 py-2.5 font-mono text-[10px] text-ink-faint">
                    Definisi pendapatan: nilai total invoice (bruto), konsisten dengan dashboard dan prediksi.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
