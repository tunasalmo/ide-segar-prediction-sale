<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { rupiah, num, pct } from '@/lib/format';

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Array,
});

const search = ref(props.filters.q ?? '');
let searchTimer = null;
watch(search, (v) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/produk', { q: v || undefined, category: props.filters.category || undefined, status: props.filters.status || undefined }, { preserveState: true, replace: true });
    }, 300);
});

function setFilter(key, value) {
    router.get('/produk', { q: search.value || undefined, [key]: value || undefined }, { preserveState: true, replace: true });
}

// ---------- form produk ----------
const modalOpen = ref(false);
const editing = ref(null); // produk yang diedit, null = tambah
const form = useForm({
    name: '',
    code: '',
    category_id: null,
    unit: 'pcs',
    price: '',
    stock: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.category_id = props.categories[0]?.id ?? null;
    modalOpen.value = true;
}

function openEdit(product) {
    editing.value = product;
    form.reset();
    form.clearErrors();
    form.name = product.name;
    form.code = product.code;
    form.category_id = product.category_id;
    form.unit = product.unit;
    form.price = product.price;
    form.stock = product.stock ?? '';
    form.is_active = product.is_active;
    modalOpen.value = true;
}

function submit() {
    form.is_active = !!form.is_active;
    if (editing.value) {
        form.put(`/produk/${editing.value.id}`, { preserveScroll: true, onSuccess: () => (modalOpen.value = false) });
    } else {
        form.post('/produk', { preserveScroll: true, onSuccess: () => (modalOpen.value = false) });
    }
}

function toggleActive(product) {
    if (product.is_active) {
        if (confirm(`Nonaktifkan produk ${product.name}? Riwayat penjualan tetap tersimpan.`)) {
            router.delete(`/produk/${product.id}`, { preserveScroll: true });
        }
    } else {
        router.post(`/produk/${product.id}/aktifkan`, {}, { preserveScroll: true });
    }
}

const statusFilter = computed(() => props.filters.status || '');
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- toolbar -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-52">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-mono text-xs text-ink-faint">⌕</span>
                    <input v-model="search" type="search" class="input w-full pl-8" placeholder="Cari nama atau kode…" aria-label="Cari produk" />
                </div>
                <select class="input w-auto" :value="props.filters.category || ''" @change="setFilter('category', $event.target.value)" aria-label="Filter kategori">
                    <option value="">Semua kategori</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select class="input w-auto" :value="statusFilter" @change="setFilter('status', $event.target.value)" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
                <div class="ml-auto">
                    <button class="btn-primary" @click="openCreate">+ Tambah produk</button>
                </div>
            </div>

            <!-- tabel -->
            <div class="panel overflow-hidden">
                <div v-if="products.data.length" class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Terjual</th>
                                <th class="text-right">Pertumbuhan</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in products.data" :key="p.id">
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
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-xs text-ink-soft">
                                        <span class="h-2 w-2 rounded-[2px]" :style="{ backgroundColor: p.category_color }"></span>
                                        {{ p.category }}
                                    </span>
                                </td>
                                <td class="num text-right">{{ rupiah(p.price) }}</td>
                                <td class="num text-right text-ink-soft">{{ num(p.sold_qty) }} {{ p.unit }}</td>
                                <td class="num text-right font-semibold" :class="(p.growth_pct ?? 0) >= 0 ? 'text-ok' : 'text-bad'">
                                    {{ pct(p.growth_pct) }}
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-xs">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="p.is_active ? 'bg-ok' : 'bg-ink-faint'"></span>
                                        <span :class="p.is_active ? 'text-ink' : 'text-ink-faint'">{{ p.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2 text-xs">
                                        <Link :href="`/produk/${p.id}`" class="font-mono text-accent hover:text-accent-dark">detail</Link>
                                        <button class="font-mono text-ink-soft hover:text-ink" @click="openEdit(p)">ubah</button>
                                        <button class="font-mono hover:text-bad" :class="p.is_active ? 'text-ink-faint' : 'text-ok'" @click="toggleActive(p)">
                                            {{ p.is_active ? 'nonaktifkan' : 'aktifkan' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Tidak ada produk" body="Ubah kata kunci pencarian, atau tambahkan produk baru.">
                    <button class="btn-secondary mt-2" @click="openCreate">+ Tambah produk</button>
                </EmptyState>
                <div class="border-t border-line/60 px-5 py-3">
                    <Pagination :links="products.links" />
                </div>
            </div>
        </div>

        <!-- modal tambah/ubah -->
        <Modal :show="modalOpen" @close="modalOpen = false" max-width="lg">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="font-display text-lg font-bold">{{ editing ? 'Ubah produk' : 'Tambah produk' }}</h2>
                <p class="mt-0.5 font-mono text-[11px] text-ink-faint">{{ editing ? editing.code : 'Produk baru akan masuk katalog' }}</p>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="p-name" value="Nama produk" />
                        <TextInput id="p-name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="p-code" value="Kode" />
                        <TextInput id="p-code" v-model="form.code" class="mt-1 block w-full font-mono uppercase" required placeholder="SPD-001" />
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="p-cat" value="Kategori" />
                        <select id="p-cat" v-model="form.category_id" class="input mt-1 block w-full" required>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <InputError :message="form.errors.category_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="p-unit" value="Satuan" />
                        <TextInput id="p-unit" v-model="form.unit" class="mt-1 block w-full" required placeholder="pcs / m2 / lembar" />
                        <InputError :message="form.errors.unit" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="p-price" value="Harga (Rp)" />
                        <TextInput id="p-price" v-model="form.price" type="number" min="0" step="500" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.price" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="p-stock" value="Stok (opsional)" />
                        <TextInput id="p-stock" v-model="form.stock" type="number" min="0" step="1" class="mt-1 block w-full" placeholder="kosong bila tidak dikelola" />
                        <InputError :message="form.errors.stock" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-line text-accent focus:ring-accent" />
                            Produk aktif (tampil di katalog)
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="modalOpen = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
