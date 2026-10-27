<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import {
    Chart,
    LineController,
    BarController,
    DoughnutController,
    LineElement,
    BarElement,
    PointElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    LineController,
    BarController,
    DoughnutController,
    LineElement,
    BarElement,
    PointElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
);

// default "kertas & tinta": tinta abu hangat untuk sumbu, hairline untuk grid
Chart.defaults.font.family = "'IBM Plex Mono', monospace";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#8A857C';
Chart.defaults.borderColor = '#E4E0D6';

const props = defineProps({
    type: { type: String, default: 'line' },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    height: { type: Number, default: null },
});

const el = ref(null);
let chart = null;

function build() {
    if (chart) {
        chart.destroy();
    }
    if (!el.value) return;
    chart = new Chart(el.value, {
        type: props.type,
        data: props.data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            ...props.options,
        },
    });
}

onMounted(build);
watch(() => [props.data, props.type], build, { deep: true });
onBeforeUnmount(() => chart?.destroy());

defineExpose({ chart: () => chart });
</script>

<template>
    <div :style="height ? { height: height + 'px' } : null" class="relative w-full">
        <canvas ref="el"></canvas>
    </div>
</template>
