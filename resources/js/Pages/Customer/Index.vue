<script setup>
import { ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
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
    customers: Object,
    filters: Object,
});

const search = ref(props.filters.q ?? '');
let timer = null;
watch(search, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/pelanggan', { q: v || undefined }, { preserveState: true, replace: true });
    }, 300);
});

const modalOpen = ref(false);
const editing = ref(null);
const form = useForm({ name: '', phone: '', address: '', notes: '' });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(c) {
    editing.value = c;
    form.reset();
    form.clearErrors();
    form.name = c.name;
    form.phone = c.phone ?? '';
    form.address = c.address ?? '';
    form.notes = c.notes ?? '';
    modalOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.put(`/pelanggan/${editing.value.id}`, { preserveScroll: true, onSuccess: () => (modalOpen.value = false) });
    } else {
        form.post('/pelanggan', { preserveScroll: true, onSuccess: () => (modalOpen.value = false) });
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-52">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-mono text-xs text-ink-faint">⌕</span>
                    <input v-model="search" type="search" class="input w-full pl-8" placeholder="Cari nama pelanggan…" aria-label="Cari pelanggan" />
                </div>
                <div class="ml-auto">
                    <button class="btn-primary" @click="openCreate">+ Tambah pelanggan</button>
                </div>
            </div>

            <div class="panel overflow-hidden">
                <div v-if="customers.data.length" class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Kontak</th>
                                <th class="text-right">Pesanan</th>
                                <th class="text-right">Nilai total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in customers.data" :key="c.id" class="cursor-pointer" @click="$inertia.visit(`/pelanggan/${c.id}`)">
                                <td>
                                    <span class="flex items-center gap-2.5">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent-soft font-display text-[10px] font-bold text-accent-dark">
                                            {{ c.name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase() }}
                                        </span>
                                        <span class="text-[13px] font-medium">{{ c.name }}</span>
                                    </span>
                                </td>
                                <td class="num text-xs text-ink-soft">{{ c.phone ?? '—' }}</td>
                                <td class="num text-right">{{ num(c.orders_count) }}</td>
                                <td class="num text-right font-semibold">{{ rupiah(c.total_value) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Tidak ada pelanggan" body="Ubah kata kunci atau tambahkan pelanggan baru." />
                <div class="border-t border-line/60 px-5 py-3">
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>

        <Modal :show="modalOpen" @close="modalOpen = false" max-width="md">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="font-display text-lg font-bold">{{ editing ? 'Ubah pelanggan' : 'Tambah pelanggan' }}</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="c-name" value="Nama" />
                        <TextInput id="c-name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="c-phone" value="No. kontak (opsional)" />
                        <TextInput id="c-phone" v-model="form.phone" class="mt-1 block w-full" placeholder="08xx…" />
                        <InputError :message="form.errors.phone" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="c-addr" value="Alamat (opsional)" />
                        <TextInput id="c-addr" v-model="form.address" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="c-notes" value="Catatan (opsional)" />
                        <TextInput id="c-notes" v-model="form.notes" class="mt-1 block w-full" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="modalOpen = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="form.processing">Simpan</button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
