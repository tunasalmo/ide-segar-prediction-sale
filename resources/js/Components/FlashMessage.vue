<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const visible = ref(false);
const kind = ref('success');
const message = ref('');

watch(
    flash,
    (f) => {
        if (f.success) {
            kind.value = 'success';
            message.value = f.success;
            visible.value = true;
            setTimeout(() => (visible.value = false), 4200);
        } else if (f.error) {
            kind.value = 'error';
            message.value = f.error;
            visible.value = true;
            setTimeout(() => (visible.value = false), 5200);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed bottom-6 right-6 z-50 flex max-w-sm items-start gap-3 rounded-card border bg-surface px-4 py-3 shadow-lift"
            :class="kind === 'success' ? 'border-ok/30' : 'border-bad/30'"
            role="status"
        >
            <span class="mt-0.5 font-mono text-sm" :class="kind === 'success' ? 'text-ok' : 'text-bad'">
                {{ kind === 'success' ? '✓' : '!' }}
            </span>
            <p class="text-sm text-ink">{{ message }}</p>
        </div>
    </Transition>
</template>
