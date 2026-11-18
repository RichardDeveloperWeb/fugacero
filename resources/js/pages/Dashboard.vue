<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CarFront,
    Clapperboard,
    GraduationCap,
    HeartPulse,
    House,
    Receipt,
    Repeat2,
    Shapes,
    ShoppingBag,
    UtensilsCrossed,
    Wallet,
    Zap,
} from '@lucide/vue';
import { computed, type Component } from 'vue';
import {
    CategoryBreakdownChart,
    ChartCard,
    MonthlyPaceChart,
    MonthlyTrendChart,
} from '@/components/charts';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Progress } from '@/components/ui/progress';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as categoriesIndex } from '@/routes/categories';
import { index as expensesIndex } from '@/routes/expenses';
import type { DashboardCharts, DashboardStats } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inicio',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    stats: DashboardStats;
    charts: DashboardCharts;
}>();

const page = usePage();
const { format } = useMoney();

const firstName = computed(
    () => page.props.auth.user.name.split(' ')[0] || page.props.auth.user.name,
);

const monthNames = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

const monthLabel = computed(() => {
    const [year, month] = props.stats.month.split('-').map(Number);
    const name = monthNames[month - 1] ?? '';

    return `${name.charAt(0).toUpperCase()}${name.slice(1)} de ${year}`;
});

const iconComponents: Record<string, Component> = {
    UtensilsCrossed,
    CarFront,
    House,
    Zap,
    Clapperboard,
    HeartPulse,
    GraduationCap,
    ShoppingBag,
    Repeat2,
    Shapes,
};

const iconFor = (name: string | null): Component =>
    name ? (iconComponents[name] ?? Shapes) : Shapes;

const usage = computed(() =>
    Math.min(100, Math.max(0, props.stats.summary.usage_percent)),
);

const hasMonthlySpending = computed(
    () =>
        props.charts.monthly_spending.spent.some((value) => value > 0) ||
        props.charts.monthly_spending.budget.some((value) => value > 0),
);

const hasCategoryBreakdown = computed(
    () =>
        props.charts.category_breakdown.categories.length > 0 ||
        props.charts.category_breakdown.other_total > 0,
);

const hasMonthlyPace = computed(
    () =>
        props.charts.monthly_pace.budget > 0 ||
        props.charts.monthly_pace.cumulative.some((value) => value > 0),
);

const dailyIdeal = computed(
    () =>
        props.charts.monthly_pace.budget /
        Math.max(1, props.charts.monthly_pace.labels.length),
);

const daysElapsed = computed(() =>
    Math.min(
        props.charts.monthly_pace.days_elapsed,
        props.charts.monthly_pace.labels.length,
    ),
);
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Inicio"
            :description="`Hola, ${firstName}. Este es tu resumen de ${monthLabel.toLowerCase()}.`"
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card>
                <CardHeader class="flex-row items-center justify-between pb-2">
                    <CardDescription>Gasto del mes</CardDescription>
                    <Receipt class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ format(stats.summary.spent) }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex-row items-center justify-between pb-2">
                    <CardDescription>Presupuesto del mes</CardDescription>
                    <Wallet class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ format(stats.summary.amount) }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex-row items-center justify-between pb-2">
                    <CardDescription>Disponible</CardDescription>
                    <Shapes class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p
                        class="text-2xl font-semibold tabular-nums"
                        :class="
                            stats.summary.available < 0
                                ? 'text-destructive'
                                : ''
                        "
                    >
                        {{ format(stats.summary.available) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <ChartCard
                class="lg:col-span-2"
                title="Evolución del gasto"
                description="Últimos seis meses comparados con tu presupuesto."
                :has-data="hasMonthlySpending"
                empty-title="Sin historial todavía"
                empty-description="Registra gastos o un presupuesto para ver tu evolución."
            >
                <MonthlyTrendChart
                    :labels="charts.monthly_spending.labels"
                    :series="[
                        {
                            label: 'Gasto',
                            values: charts.monthly_spending.spent,
                        },
                        {
                            label: 'Presupuesto',
                            values: charts.monthly_spending.budget,
                        },
                    ]"
                    :ariaLabel="'Evolución mensual del gasto y el presupuesto'"
                />
            </ChartCard>

            <ChartCard
                title="Gastos por categoría"
                :description="`Distribución de ${monthLabel.toLowerCase()}.`"
                :has-data="hasCategoryBreakdown"
                empty-title="Sin gastos este mes"
                empty-description="Tus categorías aparecerán cuando registres un gasto."
            >
                <CategoryBreakdownChart
                    :breakdown="charts.category_breakdown"
                    :ariaLabel="'Gastos del mes por categoría'"
                />
            </ChartCard>

            <ChartCard
                class="lg:col-span-3"
                title="Ritmo del mes"
                :description="`¿Vas al paso de tu presupuesto en ${monthLabel.toLowerCase()}?`"
                :has-data="hasMonthlyPace"
                empty-title="Sin ritmo para mostrar"
                empty-description="Define un presupuesto mensual para comparar tu avance."
            >
                <template #default>
                    <MonthlyPaceChart
                        :pace="charts.monthly_pace"
                        :ariaLabel="'Gasto acumulado frente al ritmo ideal del presupuesto'"
                    />
                </template>
                <template #summary>
                    <p class="text-sm text-muted-foreground tabular-nums">
                        Día {{ daysElapsed }} de
                        {{ charts.monthly_pace.labels.length }} · Ritmo ideal
                        {{ format(dailyIdeal) }} diarios
                    </p>
                </template>
            </ChartCard>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Categoría con mayor gasto</CardTitle>
                    <CardDescription
                        >Lo que más pesó en
                        {{ monthLabel.toLowerCase() }}.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="stats.top_category"
                        class="flex items-center justify-between gap-4 rounded-lg border p-4"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-md text-background"
                                :style="{
                                    backgroundColor: stats.top_category.color,
                                }"
                            >
                                <component
                                    :is="iconFor(stats.top_category.icon)"
                                    class="size-5"
                                />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ stats.top_category.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Principal categoría del mes
                                </p>
                            </div>
                        </div>
                        <p class="shrink-0 text-lg font-semibold tabular-nums">
                            {{ format(stats.top_category.total) }}
                        </p>
                    </div>

                    <Empty v-else>
                        <EmptyHeader>
                            <EmptyMedia variant="icon"><Shapes /></EmptyMedia>
                            <EmptyTitle>Sin gastos este mes</EmptyTitle>
                            <EmptyDescription>
                                Registra tu primer gasto para ver aquí tu
                                categoría principal.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Uso del presupuesto</CardTitle>
                    <CardDescription
                        >Avance de
                        {{ monthLabel.toLowerCase() }}.</CardDescription
                    >
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-baseline justify-between gap-4">
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ usage }}%
                        </p>
                        <p class="text-sm text-muted-foreground tabular-nums">
                            {{ format(stats.summary.spent) }} de
                            {{ format(stats.summary.amount) }}
                        </p>
                    </div>
                    <Progress
                        :model-value="usage"
                        aria-label="Uso del presupuesto"
                    />

                    <div class="flex flex-wrap gap-2 pt-2">
                        <Button as-child size="sm">
                            <Link :href="expensesIndex()">
                                Registrar gasto
                                <ArrowRight data-icon="inline-end" />
                            </Link>
                        </Button>
                        <Button as-child variant="outline" size="sm">
                            <Link :href="budgetsIndex()">Ver presupuesto</Link>
                        </Button>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="categoriesIndex()">Categorías</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
