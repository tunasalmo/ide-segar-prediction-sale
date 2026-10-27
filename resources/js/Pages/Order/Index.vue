<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import { rupiah, num } from '@/lib/format';

const props = defineProps({
    orders: Object,
    filters: Object,
    counts: Object,
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
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- toolbar -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-52">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-mono text-xs text-ink-faint">⌕</span>
                    <input v-model="search" type="search" class="input w-full pl-8" placeholder="Cari invoice atau pelanggan…" aria-label="Cari pesanan" />
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
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Tidak ada pesanan" body="Ubah kata kunci atau filter untuk memperluas hasil." />
                <div class="border-t border-line/60 px-5 py-3">
                    <Pagination :links="orders.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
