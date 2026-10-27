<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: String, required: true },
});

const LABELS = {
    menunggu_konfirmasi: { text: 'Menunggu konfirmasi', dot: 'bg-warn' },
    diproses: { text: 'Diproses', dot: 'bg-cmyk-cyan' },
    siap: { text: 'Siap diambil / dikirim', dot: 'bg-accent' },
    selesai: { text: 'Selesai', dot: 'bg-ok' },
    dibatalkan: { text: 'Dibatalkan', dot: 'bg-ink-faint' },
    lunas: { text: 'Lunas', dot: 'bg-ok' },
    dp: { text: 'DP', dot: 'bg-cmyk-cyan' },
    belum_bayar: { text: 'Belum bayar', dot: 'bg-bad' },
    prioritas_stok: { text: 'Prioritas stok', dot: 'bg-bad' },
    pantau: { text: 'Pantau', dot: 'bg-warn' },
    normal: { text: 'Normal', dot: 'bg-ok' },
};

const meta = computed(() => LABELS[props.value] ?? { text: props.value, dot: 'bg-ink-faint' });
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-ink">
        <span class="h-1.5 w-1.5 rounded-full" :class="meta.dot"></span>
        <span>{{ meta.text }}</span>
    </span>
</template>
