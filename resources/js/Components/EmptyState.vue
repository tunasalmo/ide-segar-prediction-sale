<script setup>
defineProps({
    title: { type: String, default: 'Belum ada data' },
    body: { type: String, default: 'Data akan muncul di sini setelah tersedia.' },
    icon: { type: String, default: '▢' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-line bg-paper font-mono text-sm text-ink-faint">
            {{ icon }}
        </div>
        <p class="font-display text-sm font-semibold text-ink">{{ title }}</p>
        <p class="max-w-sm text-xs leading-relaxed text-ink-soft">{{ body }}</p>
        <slot />
    </div>
</template>
