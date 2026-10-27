<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink transition duration-150 ease-in-out hover:bg-paper focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2"
    >
        <slot />
    </button>
</template>
