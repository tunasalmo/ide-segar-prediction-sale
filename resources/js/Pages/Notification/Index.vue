<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    notifications: Object,
});

const TYPE_LABELS = {
    spike: { label: 'Lonjakan', class: 'text-bad' },
    stock: { label: 'Stok', class: 'text-warn' },
    order: { label: 'Pesanan', class: 'text-cmyk-cyan' },
    prediction: { label: 'Prediksi', class: 'text-accent' },
    data: { label: 'Data', class: 'text-ink-faint' },
};

function markRead(n) {
    if (!n.read_at) {
        router.post(`/notifikasi/${n.id}/baca`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-ink-soft">Notifikasi dibuat dari peristiwa dan aturan model, bukan manual.</p>
                <button class="btn-secondary" @click="router.post('/notifikasi/baca-semua', {}, { preserveScroll: true })">
                    Tandai semua dibaca
                </button>
            </div>

            <div class="panel overflow-hidden">
                <div v-if="notifications.data.length">
                    <ul class="divide-y divide-line/60">
                        <li
                            v-for="n in notifications.data"
                            :key="n.id"
                            class="flex cursor-pointer items-start gap-3 px-5 py-4 transition-colors hover:bg-paper/60"
                            :class="!n.read_at ? 'bg-accent-soft/50' : ''"
                            @click="markRead(n)"
                        >
                            <span
                                class="mt-1 h-2 w-2 shrink-0 rounded-full"
                                :class="n.read_at ? 'bg-line' : 'bg-accent'"
                                aria-hidden="true"
                            ></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                    <span class="font-mono text-[10px] uppercase tracking-[0.14em]" :class="TYPE_LABELS[n.type]?.class ?? 'text-ink-faint'">
                                        {{ TYPE_LABELS[n.type]?.label ?? n.type }}
                                    </span>
                                    <span class="text-[13px] font-semibold">{{ n.title }}</span>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-ink-soft">{{ n.body }}</p>
                                <p class="mt-1 font-mono text-[10px] text-ink-faint">{{ n.created_at }}</p>
                            </div>
                            <Link
                                v-if="n.link"
                                :href="n.link"
                                class="shrink-0 font-mono text-[11px] text-accent hover:text-accent-dark"
                                @click.stop
                            >
                                buka →
                            </Link>
                        </li>
                    </ul>
                    <div class="border-t border-line/60 px-5 py-3">
                        <Pagination :links="notifications.links" />
                    </div>
                </div>
                <EmptyState v-else title="Tidak ada notifikasi" body="Notifikasi muncul saat model selesai berjalan atau ada pesanan menunggu konfirmasi." />
            </div>
        </div>
    </AppLayout>
</template>
