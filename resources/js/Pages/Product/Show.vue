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
