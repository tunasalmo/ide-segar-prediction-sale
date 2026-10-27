<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: { type: Array, required: true },
});

function pageUrl(url) {
    if (!url) return null;
    return url.replace(/\/$/, '') || '/';
}
</script>

<template>
    <nav v-if="links.length > 3" class="mt-4 flex flex-wrap items-center gap-1 text-sm" aria-label="Paginasi">
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="pageUrl(link.url)"
                :href="pageUrl(link.url)"
                preserve-scroll
                class="rounded-md px-2.5 py-1 font-mono text-xs transition-colors"
                :class="
                    link.active
                        ? 'bg-accent font-semibold text-white'
                        : 'text-ink-soft hover:bg-accent-soft hover:text-accent-dark'
                "
            >
                <span v-if="i === 0" aria-hidden="true">‹</span>
                <span v-else-if="i === links.length - 1" aria-hidden="true">›</span>
                <span v-else>{{ link.label }}</span>
            </Link>
            <span v-else key="i" class="px-2 py-1 font-mono text-xs text-ink-faint" aria-hidden="true">
                <span v-if="i === 0">‹</span>
                <span v-else-if="i === links.length - 1">›</span>
                <span v-else>{{ link.label }}</span>
            </span>
        </template>
    </nav>
</template>
