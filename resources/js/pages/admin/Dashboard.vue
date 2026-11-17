<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Receipt, Shapes, Users, Wallet } from '@lucide/vue';
import { computed } from 'vue';
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
import { dashboard as adminDashboard } from '@/routes/admin';
import type { AdminDashboardStats, AdminTopCategory } from '@/types';

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
}>();

const { format } = useMoney();

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

        <Card>
            <CardHeader>
                <CardTitle>Principales categorías</CardTitle>
                <CardDescription>
                    Ranking histórico de gasto en todas las cuentas.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Empty v-if="!hasTopCategories">
                    <EmptyHeader>
                        <EmptyMedia variant="icon"><Shapes /></EmptyMedia>
                        <EmptyTitle>Sin gastos registrados</EmptyTitle>
                        <EmptyDescription>
                            Todavía no hay gastos en el sistema.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>

                <div v-else class="space-y-4">
                    <div
                        v-for="(category, index) in stats.top_categories"
                        :key="category.id"
                        class="space-y-2 rounded-lg border p-4"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="w-5 text-sm text-muted-foreground tabular-nums"
                                >
                                    {{ index + 1 }}
                                </span>
                                <span
                                    class="size-3 shrink-0 rounded-full"
                                    :style="{ backgroundColor: category.color }"
                                />
                                <span class="font-medium">{{
                                    category.name
                                }}</span>
                                <span class="text-xs text-muted-foreground">
                                    {{ category.expenses_count }} gastos
                                </span>
                            </div>
                            <div class="flex items-baseline gap-3">
                                <span
                                    class="text-xs text-muted-foreground tabular-nums"
                                >
                                    {{ shareFor(category) }}%
                                </span>
                                <span class="font-semibold tabular-nums">
                                    {{ format(category.total) }}
                                </span>
                            </div>
                        </div>
                        <Progress
                            :model-value="shareFor(category)"
                            :aria-label="`Participación de ${category.name}`"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
