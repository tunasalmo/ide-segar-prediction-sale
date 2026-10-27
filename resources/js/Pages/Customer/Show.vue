<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    customer: Object,
    orders: Object,
    summary: Object,
});

const editOpen = ref(false);
const form = useForm({
    name: props.customer.name,
    phone: props.customer.phone ?? '',
    address: props.customer.address ?? '',
    notes: props.customer.notes ?? '',
});

function submit() {
    form.put(`/pelanggan/${props.customer.id}`, { preserveScroll: true, onSuccess: () => (editOpen.value = false) });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="panel flex flex-wrap items-start gap-5 p-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-accent font-display text-sm font-bold text-white">
                    {{ customer.name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-display text-xl font-bold tracking-tight">{{ customer.name }}</h2>
                    <p class="mt-1 font-mono text-xs text-ink-faint">
                        {{ customer.phone || 'Tanpa kontak' }}{{ customer.address ? ' · ' + customer.address : '' }}
                    </p>
                    <p v-if="customer.notes" class="mt-2 max-w-lg text-xs leading-relaxed text-ink-soft">{{ customer.notes }}</p>
                </div>
                <div class="flex gap-2">
                    <button class="btn-secondary" @click="editOpen = true">Ubah data</button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="panel p-5">
                    <p class="kicker">Jumlah pesanan</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ num(summary.total_orders) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="kicker">Nilai total</p>
                    <p class="mt-2 num text-2xl font-semibold">{{ rupiah(summary.total_value) }}</p>
                </div>
            </div>

            <div class="panel overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h3 class="font-display text-base font-bold">Riwayat pesanan</h3>
                </div>
                <div v-if="orders.data.length" class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Tanggal</th>
                                <th class="text-right">Total</th>
                                <th>Pengerjaan</th>
                                <th>Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="o in orders.data" :key="o.id" class="cursor-pointer" @click="$inertia.visit(`/pesanan/${o.id}`)">
                                <td class="num text-xs font-medium text-accent">{{ o.invoice_no }}</td>
                                <td class="num text-xs text-ink-soft">{{ o.order_date }}</td>
                                <td class="num text-right font-semibold">{{ rupiah(o.total) }}</td>
                                <td><StatusBadge :value="o.status" /></td>
                                <td><StatusBadge :value="o.payment_status" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Belum ada pesanan" body="Riwayat pesanan pelanggan ini belum tersedia." />
                <div class="border-t border-line/60 px-5 py-3">
                    <Pagination :links="orders.links" />
                </div>
            </div>

            <Link href="/pelanggan" class="font-mono text-[11px] text-accent hover:text-accent-dark">← Kembali ke daftar pelanggan</Link>
        </div>

        <Modal :show="editOpen" @close="editOpen = false" max-width="md">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="font-display text-lg font-bold">Ubah pelanggan</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="e-name" value="Nama" />
                        <TextInput id="e-name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="e-phone" value="No. kontak" />
                        <TextInput id="e-phone" v-model="form.phone" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="e-addr" value="Alamat" />
                        <TextInput id="e-addr" v-model="form.address" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="e-notes" value="Catatan" />
                        <TextInput id="e-notes" v-model="form.notes" class="mt-1 block w-full" />
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
