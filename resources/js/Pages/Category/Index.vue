<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    categories: Array,
});

const modalOpen = ref(false);
const editing = ref(null);
const form = useForm({ name: '', color: '#5A45C7', description: '' });

const SWATCHES = ['#5A45C7', '#2E9BD6', '#E0448E', '#F2B705', '#0F9D58', '#E8820C', '#0F766E', '#D62839', '#374151'];

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
    form.color = c.color;
    form.description = c.description ?? '';
    modalOpen.value = true;
}

function submit() {
    if (editing.value) {
        form.put(`/kategori/${editing.value.id}`, { preserveScroll: true, onSuccess: () => (modalOpen.value = false) });
    } else {
        form.post('/kategori', { preserveScroll: true, onSuccess: () => (modalOpen.value = false) });
    }
}

function remove(c) {
    if (confirm(`Hapus kategori ${c.name}? Kategori dengan produk tidak dapat dihapus.`)) {
        router.delete(`/kategori/${c.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-ink-soft">
                    Kategori dipakai untuk distribusi penjualan dan filter produk.
                </p>
                <button class="btn-primary" @click="openCreate">+ Tambah kategori</button>
            </div>

            <div class="panel overflow-hidden">
                <div v-if="categories.length" class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-right">Produk</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in categories" :key="c.id">
                                <td>
                                    <span class="flex items-center gap-2.5">
                                        <span class="h-3.5 w-3.5 rounded-[3px]" :style="{ backgroundColor: c.color }"></span>
                                        <span class="text-[13px] font-medium">{{ c.name }}</span>
                                        <span class="font-mono text-[10px] text-ink-faint">/{{ c.slug }}</span>
                                    </span>
                                </td>
                                <td class="num text-right">{{ c.products_count }}</td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-xs">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="c.is_active ? 'bg-ok' : 'bg-ink-faint'"></span>
                                        <span :class="c.is_active ? 'text-ink' : 'text-ink-faint'">{{ c.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2 text-xs">
                                        <button class="font-mono text-ink-soft hover:text-ink" @click="openEdit(c)">ubah</button>
                                        <button class="font-mono text-ink-faint hover:text-bad" @click="remove(c)">hapus</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Belum ada kategori" body="Tambahkan kategori produk pertama.">
                    <button class="btn-secondary mt-2" @click="openCreate">+ Tambah kategori</button>
                </EmptyState>
            </div>

            <Link href="/produk" class="font-mono text-[11px] text-accent hover:text-accent-dark">← Kembali ke produk</Link>
        </div>

        <Modal :show="modalOpen" @close="modalOpen = false" max-width="md">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="font-display text-lg font-bold">{{ editing ? 'Ubah kategori' : 'Tambah kategori' }}</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="c-name" value="Nama kategori" />
                        <TextInput id="c-name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Warna" />
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="s in SWATCHES"
                                :key="s"
                                type="button"
                                class="h-7 w-7 rounded-md border-2 transition-transform"
                                :class="form.color === s ? 'scale-110 border-ink' : 'border-transparent'"
                                :style="{ backgroundColor: s }"
                                :aria-label="'Warna ' + s"
                                @click="form.color = s"
                            ></button>
                            <input v-model="form.color" type="color" class="h-7 w-10 rounded border border-line" aria-label="Warna kustom" />
                        </div>
                        <InputError :message="form.errors.color" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="c-desc" value="Keterangan (opsional)" />
                        <TextInput id="c-desc" v-model="form.description" class="mt-1 block w-full" />
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
