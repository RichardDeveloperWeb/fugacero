<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { computed, useId } from 'vue';
import { Bar } from 'vue-chartjs';
import { useChartColors } from '@/composables/useChartColors';
import { useMoney } from '@/composables/useMoney';
import '@/lib/chart';

type TrendSeries = {
    label: string;
    values: number[];
    color?: string;
};

const props = defineProps<{
    labels: string[];
    series: TrendSeries[];
    ariaLabel: string;
}>();

const descriptionId = useId();
const colors = useChartColors();
const { format } = useMoney();

const colorAt = (index: number): string =>
    colors.palette.value[index % colors.palette.value.length] ?? '#71717a';

const shortLabels = computed(() =>
    props.labels.map((label) => {
        const [year, month] = label.split('-').map(Number);

        if (!year || !month) {
            return label;
        }

        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            timeZone: 'UTC',
        })
            .format(new Date(Date.UTC(year, month - 1, 1)))
            .replace('.', '');
    }),
);

const chartData = computed<ChartData<'bar'>>(() => ({
    labels: shortLabels.value,
    datasets: props.series.map((item, index) => ({
        label: item.label,
        data: item.values,
        backgroundColor: item.color ?? colorAt(index),
        borderRadius: 6,
        maxBarThickness: 28,
        categoryPercentage: 0.72,
        barPercentage: 0.88,
    })),
}));

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
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
                label: (context) =>
                    ` ${context.dataset.label ?? ''}: ${format(context.parsed.y ?? 0)}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: colors.text.value },
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
        <Bar
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
                    <th scope="col">Mes</th>
                    <th v-for="item in series" :key="item.label" scope="col">
                        {{ item.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(label, index) in labels" :key="label">
                    <th scope="row">{{ label }}</th>
                    <td v-for="item in series" :key="item.label">
                        {{ format(item.values[index] ?? 0) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
