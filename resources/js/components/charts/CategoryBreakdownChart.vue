<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { computed, useId } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { useChartColors } from '@/composables/useChartColors';
import { useMoney } from '@/composables/useMoney';
import '@/lib/chart';
import type { CategoryBreakdown } from '@/types';

type Slice = {
    name: string;
    color: string;
    total: number;
    count: number | null;
};

const props = defineProps<{
    breakdown: CategoryBreakdown;
    ariaLabel: string;
}>();

const descriptionId = useId();
const colors = useChartColors();
const { format } = useMoney();

const slices = computed<Slice[]>(() => {
    const items: Slice[] = props.breakdown.categories.map((category) => ({
        name: category.name,
        color: category.color,
        total: category.total,
        count: category.expenses_count,
    }));

    if (props.breakdown.other_total > 0) {
        items.push({
            name: 'Otros',
            color: colors.palette.value[4] ?? '#eab308',
            total: props.breakdown.other_total,
            count: null,
        });
    }

    return items;
});

const total = computed(() =>
    slices.value.reduce((sum, slice) => sum + slice.total, 0),
);

const chartData = computed<ChartData<'doughnut'>>(() => ({
    labels: slices.value.map((slice) => slice.name),
    datasets: [
        {
            data: slices.value.map((slice) => slice.total),
            backgroundColor: slices.value.map((slice) => slice.color),
            borderColor: colors.border.value,
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const chartOptions = computed<ChartOptions<'doughnut'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: {
            position: 'right',
            labels: {
                color: colors.text.value,
                usePointStyle: true,
                boxWidth: 8,
            },
        },
        tooltip: {
            callbacks: {
                label: (context) => {
                    const slice = slices.value[context.dataIndex];
                    const share =
                        total.value > 0 && slice
                            ? Math.round((slice.total / total.value) * 100)
                            : 0;

                    return ` ${slice?.name ?? ''}: ${format(slice?.total ?? 0)} (${share}%)`;
                },
            },
        },
    },
}));
</script>

<template>
    <div class="relative h-full w-full">
        <Doughnut
            :data="chartData"
            :options="chartOptions"
            :aria-label="ariaLabel"
            :aria-describedby="descriptionId"
        />
        <div
            class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
        >
            <span class="text-lg font-semibold tabular-nums">
                {{ format(total) }}
            </span>
            <span class="text-xs text-muted-foreground">Este mes</span>
        </div>
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
                </tr>
            </thead>
            <tbody>
                <tr v-for="slice in slices" :key="slice.name">
                    <th scope="row">{{ slice.name }}</th>
                    <td>{{ format(slice.total) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
