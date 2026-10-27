<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { rupiah, num } from '@/lib/format';

const props = defineProps({
    orders: Object,
    filters: Object,
    counts: Object,
    customers: Array,
    products: Array,
});

const search = ref(props.filters.q ?? '');
let timer = null;
watch(search, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/pesanan', { q: v || undefined, ...activeFilters() }, { preserveState: true, replace: true });
    }, 300);
});

const STATUS_FILTERS = [
    ['', 'Semua status'],
    ['menunggu_konfirmasi', `Menunggu konfirmasi (${num(props.counts.menunggu_konfirmasi)})`],
    ['diproses', `Diproses (${num(props.counts.diproses)})`],
    ['selesai', `Selesai (${num(props.counts.selesai)})`],
    ['dibatalkan', 'Dibatalkan'],
];
const PAYMENT_FILTERS = [
    ['', 'Semua pembayaran'],
    ['lunas', 'Lunas'],
    ['dp', 'DP'],
    ['belum_bayar', `Belum bayar (${num(props.counts.belum_bayar + props.counts.dp)})`],
];

function activeFilters() {
    return {
        status: props.filters.status || undefined,
        payment: props.filters.payment || undefined,
        from: props.filters.from || undefined,
        to: props.filters.to || undefined,
    };
}

function setFilter(key, value) {
    router.get('/pesanan', { q: search.value || undefined, ...activeFilters(), [key]: value || undefined }, { preserveState: true, replace: true });
}

// ---------- form tambah/ubah data penjualan ----------
const formOpen = ref(false);
const editing = ref(null);
const form = useForm({
    invoice_no: '',
    customer_id: null,
    new_customer: false,
    customer_name: '',
    customer_phone: '',
    order_date: new Date().toISOString().slice(0, 10),
    status: 'menunggu_konfirmasi',
    payment_status: 'belum_bayar',
    notes: '',
    items: [{ product_id: null, description: '', unit: '', qty: 1, unit_price: '' }],
});

const productById = computed(() => new Map(props.products.map((p) => [p.id, p])));

const statuses = {
    menunggu_konfirmasi: 'Menunggu konfirmasi',
    diproses: 'Diproses',
    siap: 'Siap diambil / dikirim',
    selesai: 'Selesai',
    dibatalkan: 'Dibatalkan',
};
const paymentStatuses = {
    lunas: 'Lunas',
    dp: 'DP',
    belum_bayar: 'Belum bayar',
};

function blankItem() {
    return { product_id: null, description: '', unit: '', qty: 1, unit_price: '' };
}

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.order_date = new Date().toISOString().slice(0, 10);
    form.items = [blankItem()];
    formOpen.value = true;
}

function openEdit(order) {
    editing.value = order;
    form.reset();
    form.clearErrors();
    form.invoice_no = order.invoice_no;
    form.customer_id = order.customer_id ?? null;
    form.new_customer = !order.customer_id;
    form.customer_name = '';
    form.customer_phone = '';
    form.order_date = order.order_date_iso;
    form.status = order.status;
    form.payment_status = order.payment_status;
    form.notes = order.notes ?? '';
    form.items = (order.items?.length ? order.items : [blankItem()]).map((i) => ({
        product_id: i.product_id ?? null,
        description: i.description ?? '',
        unit: i.unit ?? '',
        qty: i.qty,
        unit_price: i.unit_price,
    }));
    formOpen.value = true;
}

function addItemRow() {
    form.items.push(blankItem());
}

function removeItemRow(i) {
    if (form.items.length > 1) {
        form.items.splice(i, 1);
    }
}

function onProductChange(i) {
    const p = productById.value.get(form.items[i].product_id);
    if (p) {
        form.items[i].description = p.name;
        form.items[i].unit = p.unit;
        form.items[i].unit_price = p.price;
    }
}

function rowTotal(i) {
    return Number(form.items[i].qty || 0) * Number(form.items[i].unit_price || 0);
}

const grandTotal = computed(() => form.items.reduce((s, it) => s + Number(it.qty || 0) * Number(it.unit_price || 0), 0));

const allErrors = computed(() => Object.values(form.errors).filter(Boolean));

function submit() {
    form.transform((d) => ({
        ...d,
        customer_id: d.new_customer ? null : d.customer_id,
        customer_name: d.new_customer ? d.customer_name : undefined,
        customer_phone: d.new_customer ? d.customer_phone : undefined,
        items: d.items.map((it) => ({
            product_id: it.product_id,
            description: it.description,
            qty: it.qty,
            unit_price: it.unit_price,
        })),
    }));
    if (editing.value) {
        form.put(`/pesanan/${editing.value.id}`, { preserveScroll: true, onSuccess: () => (formOpen.value = false) });
    } else {
        form.post('/pesanan', { preserveScroll: true, onSuccess: () => (formOpen.value = false) });
    }
}

function removeOrder(order) {
    if (confirm(`Hapus data penjualan ${order.invoice_no}? Item pesanan ikut terhapus.`)) {
        router.delete(`/pesanan/${order.id}`, { preserveScroll: true });
    }
}

// ---------- impor CSV ----------
const importOpen = ref(false);
const importForm = useForm({ file: null });

function submitImport() {
    importForm.post('/pesanan/import', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (importOpen.value = false),
    });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- keterangan alur flowchart -->
            <p class="font-mono text-[10px] leading-relaxed text-ink-faint">
                Alur flowchart sistem usulan — langkah 4: Kelola data penjualan historis. Tambah, ubah, hapus, atau impor
                data penjualan. Model prediksi membaca data ini; jalankan ulang prediksi di menu Prediksi Penjualan
                setelah ada perubahan.
            </p>

            <!-- toolbar -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-52">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-mono text-xs text-ink-faint">⌕</span>
                    <input v-model="search" type="search" class="input w-full pl-8" placeholder="Cari invoice atau pelanggan…" aria-label="Cari data penjualan" />
                </div>
                <select class="input w-auto" :value="props.filters.status || ''" @change="setFilter('status', $event.target.value)" aria-label="Filter status">
                    <option v-for="[v, label] in STATUS_FILTERS" :key="v" :value="v">{{ label }}</option>
                </select>
                <select class="input w-auto" :value="props.filters.payment || ''" @change="setFilter('payment', $event.target.value)" aria-label="Filter pembayaran">
                    <option v-for="[v, label] in PAYMENT_FILTERS" :key="v" :value="v">{{ label }}</option>
                </select>
                <label class="flex items-center gap-1.5 text-xs text-ink-soft">
                    Dari
                    <input type="date" class="input w-auto" :value="props.filters.from" @change="setFilter('from', $event.target.value)" />
                </label>
                <label class="flex items-center gap-1.5 text-xs text-ink-soft">
                    Sampai
                    <input type="date" class="input w-auto" :value="props.filters.to" @change="setFilter('to', $event.target.value)" />
                </label>
                <div class="ml-auto flex gap-2">
                    <button class="btn-secondary" @click="importOpen = true">Impor CSV</button>
                    <button class="btn-primary" @click="openCreate">+ Tambah data</button>
                </div>
            </div>

            <!-- tabel -->
            <div class="panel overflow-hidden">
                <div v-if="orders.data.length" class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th class="text-right">Item</th>
                                <th class="text-right">Total</th>
                                <th>Pengerjaan</th>
                                <th>Pembayaran</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="o in orders.data" :key="o.id" class="cursor-pointer" @click="$inertia.visit(`/pesanan/${o.id}`)">
                                <td class="num text-xs font-medium text-accent">{{ o.invoice_no }}</td>
                                <td class="text-ink-soft">{{ o.customer }}</td>
                                <td class="num text-xs text-ink-soft">{{ o.order_date }}</td>
                                <td class="num text-right text-ink-soft">{{ o.items_count }}</td>
                                <td class="num text-right font-semibold">{{ rupiah(o.total) }}</td>
                                <td><StatusBadge :value="o.status" /></td>
                                <td><StatusBadge :value="o.payment_status" /></td>
                                <td>
                                    <div class="flex justify-end gap-2 text-xs" @click.stop>
                                        <button class="font-mono text-ink-soft hover:text-ink" @click="openEdit(o)">ubah</button>
                                        <button class="font-mono text-ink-faint hover:text-bad" @click="removeOrder(o)">hapus</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Belum ada data penjualan" body="Tambah data penjualan historis secara manual atau impor dari file CSV.">
                    <div class="mt-3 flex gap-2">
                        <button class="btn-secondary" @click="importOpen = true">Impor CSV</button>
                        <button class="btn-primary" @click="openCreate">+ Tambah data</button>
                    </div>
                </EmptyState>
                <div class="border-t border-line/60 px-5 py-3">
                    <Pagination :links="orders.links" />
                </div>
            </div>
        </div>

        <!-- ===== modal tambah/ubah data penjualan ===== -->
        <Modal :show="formOpen" @close="formOpen = false" max-width="2xl">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="font-display text-lg font-bold">{{ editing ? 'Ubah data penjualan' : 'Tambah data penjualan' }}</h2>
                <p class="mt-0.5 font-mono text-[11px] text-ink-faint">
                    {{ editing ? `${editing.invoice_no} · data historis akan dipakai model prediksi` : 'Data penjualan historis untuk model prediksi' }}
                </p>

                <div v-if="allErrors.length" class="mt-4 rounded-lg border border-bad/30 bg-bad/5 px-3.5 py-2.5 text-xs text-bad">
                    <p v-for="(e, i) in allErrors" :key="i" class="font-mono">{{ e }}</p>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="o-invoice" value="Nomor invoice (opsional — otomatis bila dikosongkan)" />
                        <TextInput id="o-invoice" v-model="form.invoice_no" class="mt-1 block w-full font-mono uppercase" placeholder="INV-…-27102026-001" />
                        <InputError :message="form.errors.invoice_no" class="mt-1" />
                    </div>

                    <!-- pelanggan -->
                    <div class="sm:col-span-2">
                        <div class="mb-1 flex items-center justify-between">
                            <InputLabel value="Pelanggan" />
                            <label class="flex items-center gap-1.5 text-xs text-ink-soft">
                                <input v-model="form.new_customer" type="checkbox" class="rounded border-line text-accent focus:ring-accent" />
                                Pelanggan baru
                            </label>
                        </div>
                        <select v-if="!form.new_customer" v-model="form.customer_id" class="input block w-full" :disabled="!customers.length">
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <TextInput v-model="form.customer_name" class="block w-full" placeholder="Nama pelanggan baru" required />
                            <TextInput v-model="form.customer_phone" class="block w-full" placeholder="Telepon (opsional)" />
                        </div>
                        <InputError :message="form.errors.customer_id || form.errors.customer_name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="o-date" value="Tanggal penjualan" />
                        <input id="o-date" v-model="form.order_date" type="date" class="input mt-1 block w-full" required />
                        <InputError :message="form.errors.order_date" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="o-status" value="Pengerjaan" />
                            <select id="o-status" v-model="form.status" class="input mt-1 block w-full">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="o-pay" value="Pembayaran" />
                            <select id="o-pay" v-model="form.payment_status" class="input mt-1 block w-full">
                                <option v-for="(label, key) in paymentStatuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <InputError :message="form.errors.payment_status" class="mt-1" />
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="o-notes" value="Catatan (opsional)" />
                        <textarea id="o-notes" v-model="form.notes" rows="2" class="input mt-1 block w-full" placeholder="Catatan pengerjaan, sisa pembayaran, dst."></textarea>
                        <InputError :message="form.errors.notes" class="mt-1" />
                    </div>

                    <!-- item -->
                    <div class="sm:col-span-2">
                        <div class="mb-2 flex items-baseline justify-between">
                            <InputLabel value="Item penjualan" />
                            <button type="button" class="font-mono text-[11px] text-accent hover:text-accent-dark" @click="addItemRow">+ Tambah item</button>
                        </div>
                        <div class="overflow-hidden rounded-lg border border-line">
                            <div v-for="(it, i) in form.items" :key="i" class="grid grid-cols-12 gap-2 border-b border-line/60 p-2.5 last:border-0">
                                <select v-model="it.product_id" class="input col-span-12 sm:col-span-6" @change="onProductChange(i)">
                                    <option :value="null">— pilih produk —</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }} ({{ p.unit }})</option>
                                </select>
                                <input v-model="it.description" type="text" class="input col-span-8 sm:col-span-2" placeholder="Deskripsi" />
                                <input v-model="it.qty" type="number" min="0" step="0.01" class="input col-span-4 sm:col-span-1" placeholder="Qty" />
                                <input v-model="it.unit_price" type="number" min="0" step="500" class="input col-span-4 sm:col-span-2" placeholder="Harga" />
                                <div class="col-span-6 flex items-center justify-end gap-2 sm:col-span-1">
                                    <span class="num text-xs font-semibold text-ink">{{ rupiah(rowTotal(i)) }}</span>
                                    <button type="button" class="font-mono text-xs text-ink-faint hover:text-bad" title="Hapus item" @click="removeItemRow(i)">✕</button>
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 text-right text-sm">
                            <span class="kicker mr-2">Total nilai</span>
                            <span class="num text-lg font-semibold">{{ rupiah(grandTotal) }}</span>
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="formOpen = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- ===== modal impor CSV ===== -->
        <Modal :show="importOpen" @close="importOpen = false" max-width="lg">
            <form @submit.prevent="submitImport" class="p-6">
                <h2 class="font-display text-lg font-bold">Impor data penjualan (CSV)</h2>
                <p class="mt-0.5 font-mono text-[11px] text-ink-faint">Langkah 4 flowchart — impor data penjualan historis sekaligus</p>

                <div class="mt-5 rounded-lg border border-line bg-paper/60 p-3.5 text-xs leading-relaxed text-ink-soft">
                    <p class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-faint">Format kolom</p>
                    <code class="font-mono">invoice_no, tanggal, pelanggan, kode_produk, qty, harga_satuan, status, pembayaran, catatan</code>
                    <p class="mt-1.5">
                        Satu invoice boleh tersebar di beberapa baris (satu baris per item). Invoice yang sudah ada otomatis
                        dilewati. Tanggal: <code class="font-mono">27/10/2026</code> atau <code class="font-mono">2026-10-27</code>.
                    </p>
                    <a href="/pesanan/import/template" class="mt-2 inline-block font-mono text-accent hover:text-accent-dark">↓ Unduh template CSV</a>
                </div>

                <div class="mt-4">
                    <InputLabel for="csv-file" value="File CSV" />
                    <input
                        id="csv-file"
                        type="file"
                        accept=".csv,.txt,text/csv"
                        class="mt-1 block w-full text-sm text-ink file:mr-3 file:rounded-md file:border-0 file:bg-accent file:px-3 file:py-1.5 file:font-mono file:text-xs file:text-white hover:file:bg-accent-dark"
                        @input="importForm.file = $event.target.files[0]"
                    />
                    <InputError :message="importForm.errors.file" class="mt-1" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="importOpen = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="importForm.processing">
                        {{ importForm.processing ? 'Mengimpor…' : 'Impor file' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
