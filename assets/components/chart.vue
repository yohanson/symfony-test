<template>
    <n-config-provider>
        <div class="histogram-container">
            <canvas ref="chartCanvas"></canvas>
        </div>
    </n-config-provider>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { NConfigProvider } from 'naive-ui';
import Chart from 'chart.js/auto';
import { toISODateString } from '/assets/utils/date.js';

const chartCanvas = ref(null);

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    label: {
        type: String,
        required: false,
    },
    dateRange: {
        type: Array,
        required: true,
    }
});

const chartData = computed(() => {
    if (!props.data.length) return {};
    let data = initData();
    props.data.forEach((row) => {
        let label = toISODateString(row.date);
        data[label] += row.price * row.quantity;
    });

    return {
        labels: Object.keys(data).sort(),
        datasets: [{
            label: props.label,
            data: data,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
        }],
    }
});

function initData() {
    if (!props.dateRange) {
        return {};
    }
    let data = {};
    for (let date = props.dateRange[0]; date <= props.dateRange[1]; date += 86400000) {
        data[toISODateString(date)] = 0;
    }
    return data;
}

let chartInstance = null;

onMounted(() => {
    const ctx = chartCanvas.value.getContext('2d');

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            scales: {
                x: { title: { display: true, text: 'Date' } },
                y: { title: { display: true, text: 'Sales, ₽' }, beginAtZero: true },
            },
            barPercentage: 1.0,
            categoryPercentage: 1.0,
            plugins: {
                legend: {
                    display: false,
                }
            }
        },
    });
});

watch(chartData, (newData) => {
    if (chartInstance) {
        chartInstance.data = newData;
        chartInstance.update();
    }
});
</script>

<style scoped>
.histogram-container {
    max-width: 600px;
    margin: 20px;
}
</style>
