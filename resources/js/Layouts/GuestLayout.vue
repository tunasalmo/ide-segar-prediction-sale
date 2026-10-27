<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen">
        <!-- panel brand: tinta & CMYK -->
        <div class="halftone relative hidden w-[44%] flex-col justify-between overflow-hidden bg-ink p-10 text-[#EDE9E1] lg:flex">
            <div class="reg-mark">
                <span class="bg-cmyk-cyan"></span>
                <span class="bg-cmyk-magenta"></span>
                <span class="bg-cmyk-yellow"></span>
                <span class="bg-white/80"></span>
            </div>
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 grid-cols-2 overflow-hidden rounded-[4px] ring-1 ring-white/15" aria-hidden="true">
                    <span class="bg-cmyk-cyan"></span>
                    <span class="bg-cmyk-magenta"></span>
                    <span class="bg-cmyk-yellow"></span>
                    <span class="bg-white"></span>
                </div>
                <div class="leading-tight">
                    <p class="font-display text-lg font-extrabold tracking-tight text-white">IDE SEGAR</p>
                    <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-accent-line">Printing</p>
                </div>
            </div>

            <div class="space-y-6">
                <h1 class="max-w-md font-display text-3xl font-bold leading-tight tracking-tight text-white">
                    Prediksi penjualan untuk perencanaan produksi yang lebih tenang.
                </h1>
                <ul class="space-y-3 text-sm text-[#C9C4B8]">
                    <li class="flex items-center gap-3">
                        <span class="font-mono text-[10px] text-accent-line">01</span>
                        Ringkasan metrik penjualan dari data transaksi nyata
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="font-mono text-[10px] text-accent-line">02</span>
                        Proyeksi aktual vs prediksi, 7/30/90 hari
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="font-mono text-[10px] text-accent-line">03</span>
                        Rekomendasi prioritas stok berbasis evaluasi model
                    </li>
                </ul>
            </div>

            <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-white/30">
                Dashboard internal · CV. Faruqi Mandiri Sejahtera
            </p>
        </div>

        <!-- panel form: kertas -->
        <div class="flex flex-1 flex-col items-center justify-center bg-paper px-6 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="grid h-9 w-9 grid-cols-2 overflow-hidden rounded-[4px] ring-1 ring-ink/15" aria-hidden="true">
                        <span class="bg-cmyk-cyan"></span>
                        <span class="bg-cmyk-magenta"></span>
                        <span class="bg-cmyk-yellow"></span>
                        <span class="bg-ink"></span>
                    </div>
                    <div class="leading-tight">
                        <p class="font-display text-base font-extrabold tracking-tight text-ink">IDE SEGAR</p>
                        <p class="font-mono text-[9px] uppercase tracking-[0.3em] text-accent">Printing</p>
                    </div>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
