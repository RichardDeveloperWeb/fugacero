<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Receipt, Users, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import {
    AdoptionChart,
    AdminCategoryChart,
    ChartCard,
    MonthlyTrendChart,
} from '@/components/charts';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import type {
    AdminDashboardCharts,
    AdminDashboardStats,
    AdminTopCategory,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Panel de administración', href: adminDashboard() },
        ],
    },
});

const props = defineProps<{
    stats: AdminDashboardStats;
    charts: AdminDashboardCharts;
}>();

const { format } = useMoney();

const hasMonthlySpending = computed(() =>
    props.charts.monthly_spending.total_spent.some((value) => value > 0),
);

const hasTopCategories = computed(() => props.stats.top_categories.length > 0);

function shareFor(category: AdminTopCategory): number {
    if (props.stats.total_spent <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round((category.total / props.stats.total_spent) * 1000) / 10,
    );
}
</script>

<template>
    <Head title="Panel de administración" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Panel de administración"
                description="Métricas globales de todas las cuentas de FugaCero."
            />
            <Button as-child variant="outline" size="sm">
                <Link :href="dashboard()">
                    <ArrowLeft data-icon="inline-start" />
                    Volver a Inicio
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex-row items-center justify-between pb-2">
                    <CardDescription>Usuarios registrados</CardDescription>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ stats.users }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex-row items-center justify-between pb-2">
                    <CardDescription>Usuarios con gastos</CardDescription>
                    <Receipt class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ stats.users_with_expenses }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex-row items-center justify-between pb-2">
                    <CardDescription>Gasto total global</CardDescription>
                    <Wallet class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ format(stats.total_spent) }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex-row items-center justify-between pb-2">
                    <CardDescription>Promedio por usuario</CardDescription>
                    <Users class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ format(stats.average_per_user) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <ChartCard
                class="lg:col-span-2"
                title="Evolución global del gasto"
                description="Últimos doce meses en todas las cuentas."
                :has-data="hasMonthlySpending"
                empty-title="Sin gastos registrados"
                empty-description="La tendencia global aparecerá cuando haya gastos."
            >
                <MonthlyTrendChart
                    :labels="charts.monthly_spending.labels"
                    :series="[
                        {
                            label: 'Gasto global',
                            values: charts.monthly_spending.total_spent,
                        },
                    ]"
                    :ariaLabel="'Evolución mensual global del gasto'"
                />
            </ChartCard>

            <ChartCard
                title="Adopción de usuarios"
                description="Cuentas que ya registraron gastos."
                :has-data="stats.users > 0"
                empty-title="Sin usuarios"
                empty-description="Todavía no hay cuentas registradas."
            >
                <AdoptionChart
                    :users="stats.users"
                    :users-with-expenses="stats.users_with_expenses"
                    :ariaLabel="'Usuarios con gastos frente a usuarios sin gastos'"
                />
            </ChartCard>
        </div>

        <ChartCard
            title="Principales categorías"
            description="Ranking histórico de gasto en todas las cuentas."
            :has-data="hasTopCategories"
            empty-title="Sin gastos registrados"
            empty-description="Todavía no hay gastos en el sistema."
        >
            <AdminCategoryChart
                :categories="stats.top_categories"
                :ariaLabel="'Principales categorías de gasto'"
            />
            <template #summary>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                    <div
                        v-for="category in stats.top_categories"
                        :key="category.id"
                        class="flex min-w-0 items-center justify-between gap-2 rounded-lg border p-2"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="size-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: category.color }"
                            />
                            <span class="truncate text-sm">{{
                                category.name
                            }}</span>
                        </div>
                        <span
                            class="shrink-0 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ format(category.total) }} ·
                            {{ shareFor(category) }}%
                        </span>
                    </div>
                </div>
            </template>
        </ChartCard>
    </div>
</template>
