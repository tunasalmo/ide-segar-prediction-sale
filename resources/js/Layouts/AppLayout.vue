<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const unreadCount = computed(() => page.props.auth?.unreadNotifications ?? 0);
const pendingOrders = computed(() => page.props.auth?.pendingOrders ?? 0);

// ---------- judul halaman dari nama route ----------
const TITLES = {
    dashboard: ['Dashboard', 'OPERASIONAL'],
    prediksi: ['Prediksi Penjualan', 'OPERASIONAL'],
    'produk.index': ['Produk', 'DATA'],
    'produk.show': ['Detail Produk', 'DATA'],
    'kategori.index': ['Kategori Produk', 'DATA'],
    'pesanan.index': ['Pesanan', 'DATA'],
    'pesanan.show': ['Detail Pesanan', 'DATA'],
    'pelanggan.index': ['Pelanggan', 'DATA'],
    'pelanggan.show': ['Detail Pelanggan', 'DATA'],
    'laporan.index': ['Laporan', 'DATA'],
    'pengaturan.index': ['Pengaturan', 'SISTEM'],
    'notifikasi.index': ['Notifikasi', 'SISTEM'],
};
const currentTitle = computed(() => {
    const name = route().current() ?? '';
    return TITLES[name] ?? ['—', ''];
});

const mobileOpen = ref(false);

// ---------- menu ----------
const NAV = [
    {
        section: 'Operasional',
        items: [
            { label: 'Dashboard', href: '/', icon: '◧', match: 'dashboard' },
            { label: 'Prediksi Penjualan', href: '/prediksi', icon: '◮', match: 'prediksi' },
        ],
    },
    {
        section: 'Data',
        items: [
            { label: 'Pesanan', href: '/pesanan', icon: '▤', match: 'pesanan', badge: () => pendingOrders.value, badgeTitle: 'Pesanan menunggu konfirmasi' },
            { label: 'Produk', href: '/produk', icon: '▣', match: 'produk' },
            { label: 'Pelanggan', href: '/pelanggan', icon: '◈', match: 'pelanggan' },
            { label: 'Laporan', href: '/laporan', icon: '▦', match: 'laporan' },
        ],
    },
    {
        section: 'Sistem',
        items: [
            { label: 'Kategori', href: '/kategori', icon: '◫', match: 'kategori' },
            { label: 'Notifikasi', href: '/notifikasi', icon: '◉', match: 'notifikasi', badge: () => unreadCount.value, badgeTitle: 'Belum dibaca' },
            { label: 'Pengaturan', href: '/pengaturan', icon: '⚙', match: 'pengaturan' },
        ],
    },
];

function isActive(item) {
    const current = route().current() ?? '';
    return current.startsWith(item.match);
}

// ---------- pencarian global ----------
const searchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
let searchTimer = null;

async function doSearch() {
    const q = searchQuery.value.trim();
    if (q.length < 2) {
        searchResults.value = [];
        return;
    }
    searching.value = true;
    try {
        const { data } = await axios.get('/api/search', { params: { q } });
        searchResults.value = data.results ?? [];
    } catch {
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(doSearch, 260);
}

function goToProduct(url) {
    searchOpen.value = false;
    searchQuery.value = '';
    router.visit(url);
}

function onSearchKeydown(e) {
    if (e.key === 'Enter' && searchResults.value.length > 0) {
        goToProduct(searchResults.value[0].url);
    }
}

// ---------- notifikasi ----------
const notifOpen = ref(false);
const recentNotifications = computed(() => page.props.notifications?.slice(0, 6) ?? []);

function markAllRead() {
    router.post('/notifikasi/baca-semua', {}, { preserveScroll: true });
}

function logout() {
    useForm({}).post('/logout', { preserveScroll: false });
}

const initials = computed(() => {
    const name = user.value?.name ?? '?';
    return name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
});
</script>

<template>
    <Head :title="currentTitle[0]" />
    <div class="min-h-screen bg-paper">
        <!-- overlay mobile -->
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-30 bg-ink/50 lg:hidden"
            @click="mobileOpen = false"
        ></div>

        <!-- ============ SIDEBAR ============ -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-[264px] flex-col bg-ink text-[#EDE9E1] transition-transform lg:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- brand -->
            <div class="flex items-center gap-3 px-5 pb-6 pt-6">
                <div class="grid h-9 w-9 grid-cols-2 overflow-hidden rounded-[4px] ring-1 ring-white/15" aria-hidden="true">
                    <span class="bg-cmyk-cyan"></span>
                    <span class="bg-cmyk-magenta"></span>
                    <span class="bg-cmyk-yellow"></span>
                    <span class="bg-white"></span>
                </div>
                <div class="leading-tight">
                    <p class="font-display text-[15px] font-extrabold tracking-tight text-white">IDE SEGAR</p>
                    <p class="font-mono text-[9px] uppercase tracking-[0.3em] text-accent-line">Printing</p>
                </div>
            </div>

            <div class="mx-5 border-t border-white/10"></div>

            <!-- nav -->
            <nav class="scrollbar-ink flex-1 overflow-y-auto px-3 py-4" aria-label="Menu utama">
                <div v-for="group in NAV" :key="group.section" class="mb-5">
                    <p class="px-2 pb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-white/35">
                        {{ group.section }}
                    </p>
                    <ul class="space-y-0.5">
                        <li v-for="(item, i) in group.items" :key="item.href">
                            <Link
                                :href="item.href"
                                class="group relative flex items-center gap-2.5 rounded-md px-2.5 py-2 text-[13px] font-medium transition-colors"
                                :class="isActive(item) ? 'bg-white/[0.08] text-white' : 'text-[#C9C4B8] hover:bg-white/[0.05] hover:text-white'"
                            >
                                <!-- penanda aktif: bar violet -->
                                <span
                                    v-if="isActive(item)"
                                    class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-full bg-accent-line"
                                    aria-hidden="true"
                                ></span>
                                <span class="w-5 font-mono text-[11px]" :class="isActive(item) ? 'text-accent-line' : 'text-white/30'">{{ item.icon }}</span>
                                <span class="flex-1">{{ item.label }}</span>
                                <span
                                    v-if="item.badge && item.badge() > 0"
                                    class="rounded-full bg-accent-line px-1.5 py-0.5 font-mono text-[10px] font-semibold leading-none text-accent-deep"
                                    :title="item.badgeTitle"
                                >
                                    {{ item.badge() }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- footer: identitas pengguna -->
            <div class="border-t border-white/10 px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-accent font-display text-[11px] font-bold text-white">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1 leading-tight">
                        <p class="truncate text-[13px] font-semibold text-white">{{ user?.name }}</p>
                        <p class="truncate font-mono text-[10px] text-white/40">{{ user?.email }}</p>
                    </div>
                    <button
                        class="rounded p-1 text-white/40 transition-colors hover:bg-white/10 hover:text-white"
                        title="Keluar"
                        @click="logout"
                    >
                        <span class="font-mono text-xs">⏻</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- ============ AREA KONTEN ============ -->
        <div class="lg:pl-[264px]">
            <!-- header -->
            <header class="sticky top-0 z-20 border-b border-line bg-paper/90 backdrop-blur">
                <div class="flex h-16 items-center gap-4 px-5 lg:px-8">
                    <button
                        class="rounded-md border border-line bg-surface p-2 lg:hidden"
                        aria-label="Buka menu"
                        @click="mobileOpen = true"
                    >
                        <span class="font-mono text-xs">☰</span>
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="kicker truncate">Admin — {{ currentTitle[1] }}</p>
                        <h1 class="truncate font-display text-lg font-bold leading-tight tracking-tight">
                            {{ currentTitle[0] }}
                        </h1>
                    </div>

                    <!-- pencarian produk -->
                    <div class="relative hidden w-72 md:block">
                        <label for="global-search" class="sr-only">Cari produk</label>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-mono text-xs text-ink-faint">⌕</span>
                        <input
                            id="global-search"
                            v-model="searchQuery"
                            type="search"
                            class="input w-full pl-8"
                            placeholder="Cari produk…"
                            @input="onSearchInput"
                            @focus="searchOpen = true"
                            @keydown="onSearchKeydown"
                        />
                        <div
                            v-if="searchOpen && searchQuery.trim().length >= 2"
                            class="absolute left-0 right-0 top-full z-50 mt-1.5 overflow-hidden rounded-card border border-line bg-surface shadow-lift"
                        >
                            <div v-if="searching" class="px-4 py-3 font-mono text-xs text-ink-faint">Mencari…</div>
                            <ul v-else-if="searchResults.length" class="max-h-72 overflow-y-auto">
                                <li v-for="r in searchResults" :key="r.id">
                                    <button
                                        class="flex w-full items-center gap-3 px-3.5 py-2.5 text-left transition-colors hover:bg-accent-soft"
                                        @click="goToProduct(r.url)"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: r.category_color }"></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[13px] font-medium">{{ r.name }}</span>
                                            <span class="block font-mono text-[10px] text-ink-faint">{{ r.code }} · {{ r.category }}</span>
                                        </span>
                                        <span class="num text-xs text-ink-soft">{{ r.price.toLocaleString('id-ID') }}</span>
                                    </button>
                                </li>
                                <li class="border-t border-line/60 px-3.5 py-2 font-mono text-[10px] text-ink-faint">
                                    {{ searchResults.length }} hasil — Enter untuk membuka yang pertama
                                </li>
                            </ul>
                            <div v-else class="px-4 py-3 text-xs text-ink-soft">Tidak ada produk yang cocok.</div>
                        </div>
                    </div>

                    <!-- lonceng -->
                    <div class="relative">
                        <button
                            class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-surface text-ink transition-colors hover:border-ink-faint"
                            aria-label="Notifikasi"
                            :aria-expanded="notifOpen"
                            @click="notifOpen = !notifOpen"
                        >
                            <span class="font-mono text-sm">◉</span>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-bad px-1 font-mono text-[9px] font-semibold text-white"
                            >
                                {{ unreadCount > 99 ? '99+' : unreadCount }}
                            </span>
                        </button>
                        <div
                            v-if="notifOpen"
                            class="absolute right-0 top-full z-50 mt-1.5 w-80 overflow-hidden rounded-card border border-line bg-surface shadow-lift"
                        >
                            <div class="flex items-center justify-between border-b border-line px-4 py-2.5">
                                <p class="kicker">Notifikasi</p>
                                <button class="font-mono text-[11px] text-accent hover:text-accent-dark" @click="markAllRead">
                                    Tandai dibaca
                                </button>
                            </div>
                            <ul class="max-h-80 overflow-y-auto">
                                <li v-for="n in recentNotifications" :key="n.id" class="border-b border-line/50 last:border-0">
                                    <Link :href="n.link ?? '/notifikasi'" class="block px-4 py-2.5 transition-colors hover:bg-paper">
                                        <p class="flex items-baseline gap-2 text-[13px] font-medium">
                                            <span class="h-1.5 w-1.5 shrink-0 translate-y-[-1px] rounded-full" :class="n.read_at ? 'bg-line' : 'bg-accent'"></span>
                                            {{ n.title }}
                                        </p>
                                        <p class="mt-0.5 line-clamp-2 pl-3.5 text-xs leading-relaxed text-ink-soft">{{ n.body }}</p>
                                    </Link>
                                </li>
                                <li v-if="!recentNotifications.length" class="px-4 py-6 text-center text-xs text-ink-soft">
                                    Tidak ada notifikasi.
                                </li>
                            </ul>
                            <Link href="/notifikasi" class="block border-t border-line px-4 py-2.5 text-center text-xs font-semibold text-accent hover:bg-accent-soft">
                                Semua notifikasi →
                            </Link>
                        </div>
                    </div>

                    <!-- akun -->
                    <Link href="/pengaturan" class="hidden items-center gap-2.5 rounded-lg border border-line bg-surface py-1.5 pl-1.5 pr-3 transition-colors hover:border-ink-faint sm:flex">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent font-display text-[9px] font-bold text-white">{{ initials }}</span>
                        <span class="hidden text-xs font-semibold md:inline">{{ user?.name.split(' ')[0] }}</span>
                    </Link>
                </div>
            </header>

            <!-- konten halaman -->
            <main class="px-5 py-6 lg:px-8 lg:py-8">
                <slot />
            </main>

            <footer class="px-5 pb-8 lg:px-8">
                <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink-faint">
                    IDE Segar Printing — prediksi adalah estimasi pendukung keputusan, bukan jaminan hasil
                </p>
            </footer>
        </div>

        <FlashMessage />
    </div>
</template>
