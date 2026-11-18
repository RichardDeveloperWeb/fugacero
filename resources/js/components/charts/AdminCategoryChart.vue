<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { computed, useId } from 'vue';
import { Bar } from 'vue-chartjs';
import { useChartColors } from '@/composables/useChartColors';
import { useMoney } from '@/composables/useMoney';
import '@/lib/chart';
import type { AdminTopCategory } from '@/types';

const props = defineProps<{
    categories: AdminTopCategory[];
    ariaLabel: string;
}>();

const descriptionId = useId();
const colors = useChartColors();
const { format } = useMoney();

const chartData = computed<ChartData<'bar'>>(() => ({
    labels: props.categories.map((category) => category.name),
    datasets: [
        {
            label: 'Gasto',
            data: props.categories.map((category) => category.total),
            backgroundColor: props.categories.map((category) => category.color),
            borderRadius: 6,
            maxBarThickness: 24,
        },
    ],
}));

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (context) => {
                    const category = props.categories[context.dataIndex];

                    return ` ${format(context.parsed.x ?? 0)} · ${category?.expenses_count ?? 0} gastos`;
                },
            },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            grid: { color: colors.grid.value },
            border: { display: false },
            ticks: {
                color: colors.text.value,
                maxTicksLimit: 6,
                callback: (value) => format(Number(value ?? 0)),
            },
        },
        y: {
            grid: { display: false },
            border: { display: false },
            ticks: { color: colors.text.value },
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
                    <th scope="col">Categoría</th>
                    <th scope="col">Total</th>
                    <th scope="col">Gastos</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="category in categories" :key="category.id">
                    <th scope="row">{{ category.name }}</th>
                    <td>{{ format(category.total) }}</td>
                    <td>{{ category.expenses_count }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
