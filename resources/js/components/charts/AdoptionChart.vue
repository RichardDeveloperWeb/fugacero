<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { computed, useId } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { useChartColors } from '@/composables/useChartColors';
import '@/lib/chart';

const props = defineProps<{
    users: number;
    usersWithExpenses: number;
    ariaLabel: string;
}>();

const descriptionId = useId();
const colors = useChartColors();

const withoutExpenses = computed(() =>
    Math.max(0, props.users - props.usersWithExpenses),
);

const percentage = computed(() =>
    props.users > 0
        ? Math.round((props.usersWithExpenses / props.users) * 100)
        : 0,
);

const chartData = computed<ChartData<'doughnut'>>(() => ({
    labels: ['Con gastos', 'Sin gastos'],
    datasets: [
        {
            data: [props.usersWithExpenses, withoutExpenses.value],
            backgroundColor: [
                colors.palette.value[1] ?? '#3b82f6',
                colors.palette.value[3] ?? '#a855f7',
            ],
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
                    ` ${context.label}: ${context.parsed} usuarios`,
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
            <span class="text-2xl font-semibold tabular-nums">
                {{ percentage }}%
            </span>
            <span class="text-xs text-muted-foreground">Activos</span>
        </div>
        <table :id="descriptionId" class="sr-only">
            <caption>
                {{
                    ariaLabel
                }}
            </caption>
            <thead>
                <tr>
                    <th scope="col">Estado</th>
                    <th scope="col">Usuarios</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row">Con gastos</th>
                    <td>{{ usersWithExpenses }}</td>
                </tr>
                <tr>
                    <th scope="row">Sin gastos</th>
                    <td>{{ withoutExpenses }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
