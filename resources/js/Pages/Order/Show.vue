<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { rupiah, num } from '@/lib/format';

const props = defineProps({
    order: Object,
    statuses: Object,
    paymentStatuses: Object,
});

const form = useForm({
    status: props.order.status,
    payment_status: props.order.payment_status,
});

function submit() {
    form.put(`/pesanan/${props.order.id}/status`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- header pesanan -->
            <div class="panel flex flex-wrap items-start gap-5 p-6">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display text-xl font-bold tracking-tight">{{ order.invoice_no }}</h2>
                        <StatusBadge :value="order.status" />
                        <StatusBadge :value="order.payment_status" />
                    </div>
                    <p class="mt-1 font-mono text-xs text-ink-faint">
                        Dibuat {{ order.order_date }} · tercatat {{ order.created_at }}
                    </p>
                    <p class="mt-3 text-sm text-ink-soft">
                        Pelanggan:
                        <Link :href="`/pelanggan/${order.customer.id}`" class="font-medium text-accent hover:text-accent-dark">
                            {{ order.customer.name }}
                        </Link>
                        <span v-if="order.customer.phone" class="num ml-2 text-xs">{{ order.customer.phone }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="kicker">Total nilai</p>
                    <p class="mt-1 num text-2xl font-semibold">{{ rupiah(order.total) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <!-- item -->
                <div class="panel xl:col-span-2">
                    <div class="border-b border-line px-5 py-4">
                        <h3 class="font-display text-base font-bold">Item pesanan</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Harga satuan</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="i in order.items" :key="i.id">
                                    <td>
                                        <Link v-if="i.product_id" :href="`/produk/${i.product_id}`" class="block text-[13px] font-medium text-accent hover:text-accent-dark">
                                            {{ i.description }}
                                        </Link>
                                        <span v-else class="block text-[13px] text-ink-soft">{{ i.description }}</span>
                                        <span v-if="i.code" class="font-mono text-[10px] text-ink-faint">{{ i.code }}</span>
                                    </td>
                                    <td class="num text-right">{{ num(i.qty) }} {{ i.unit }}</td>
                                    <td class="num text-right text-ink-soft">{{ rupiah(i.unit_price) }}</td>
                                    <td class="num text-right font-semibold">{{ rupiah(i.total) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right font-mono text-[11px] uppercase tracking-[0.12em] text-ink-faint">Total</td>
                                    <td class="num text-right font-semibold">{{ rupiah(order.total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- perbarui status -->
                <div class="panel p-5">
                    <p class="kicker">Alur pengerjaan</p>
                    <h3 class="mt-1 font-display text-base font-bold">Perbarui status</h3>
                    <form @submit.prevent="submit" class="mt-4 space-y-4">
                        <div>
                            <label for="o-status" class="mb-1 block text-xs font-medium text-ink-soft">Status pengerjaan</label>
                            <select id="o-status" v-model="form.status" class="input block w-full">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs text-bad">{{ form.errors.status }}</p>
                        </div>
                        <div>
                            <label for="o-pay" class="mb-1 block text-xs font-medium text-ink-soft">Status pembayaran</label>
                            <select id="o-pay" v-model="form.payment_status" class="input block w-full">
                                <option v-for="(label, key) in paymentStatuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.payment_status" class="mt-1 text-xs text-bad">{{ form.errors.payment_status }}</p>
                        </div>
                        <button type="submit" class="btn-primary w-full" :disabled="form.processing">Simpan status</button>
                    </form>
                    <p class="mt-4 border-t border-line/60 pt-3 font-mono text-[10px] leading-relaxed text-ink-faint">
                        Alur: Menunggu konfirmasi → Diproses → Siap diambil/dikirim → Selesai. Pembayaran dipisah dari status pengerjaan.
                    </p>
                </div>
            </div>

            <Link href="/pesanan" class="font-mono text-[11px] text-accent hover:text-accent-dark">← Kembali ke daftar pesanan</Link>
        </div>
    </AppLayout>
</template>
