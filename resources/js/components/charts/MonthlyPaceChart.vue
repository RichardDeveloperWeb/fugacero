<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { computed, useId } from 'vue';
import { Line } from 'vue-chartjs';
import { useChartColors } from '@/composables/useChartColors';
import { useMoney } from '@/composables/useMoney';
import '@/lib/chart';
import type { MonthlyPace } from '@/types';

const props = defineProps<{
    pace: MonthlyPace;
    ariaLabel: string;
}>();

const descriptionId = useId();
const colors = useChartColors();
const { format } = useMoney();

const chartData = computed<ChartData<'line'>>(() => ({
    labels: props.pace.labels,
    datasets: [
        {
            label: 'Gasto acumulado',
            data: props.pace.cumulative,
            borderColor: colors.palette.value[0] ?? '#f97316',
            backgroundColor: `${colors.palette.value[0] ?? '#f97316'}22`,
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
        },
        {
            label: 'Ritmo ideal',
            data: props.pace.ideal,
            borderColor: colors.palette.value[1] ?? '#3b82f6',
            borderWidth: 2,
            borderDash: [6, 4],
            pointRadius: 0,
            tension: 0,
        },
    ],
}));

const chartOptions = computed<ChartOptions<'line'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: colors.text.value,
                usePointStyle: true,
                boxWidth: 8,
            },
        },
        tooltip: {
            callbacks: {
                title: (items) => `Día ${items[0]?.label ?? ''}`,
                label: (context) =>
                    ` ${context.dataset.label ?? ''}: ${format(context.parsed.y ?? 0)}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
                color: colors.text.value,
                maxTicksLimit: 8,
            },
        },
        y: {
            beginAtZero: true,
            grid: { color: colors.grid.value },
            border: { display: false },
            ticks: {
                color: colors.text.value,
                maxTicksLimit: 6,
                callback: (value) => format(Number(value ?? 0)),
            },
        },
    },
}));
</script>

<template>
    <div class="relative h-full w-full">
        <Line
            :data="chartData"
            :options="chartOptions"
            :aria-label="ariaLabel"
            :aria-describedby="descriptionId"
        />
        <table :id="descriptionId" class="sr-only">
            <caption>
                {{
                    ariaLabel
                }}
            </caption>
            <thead>
                <tr>
                    <th scope="col">Día</th>
                    <th scope="col">Gasto acumulado</th>
                    <th scope="col">Ritmo ideal</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(label, index) in pace.labels" :key="label">
                    <th scope="row">{{ label }}</th>
                    <td>{{ format(pace.cumulative[index] ?? 0) }}</td>
                    <td>{{ format(pace.ideal[index] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
