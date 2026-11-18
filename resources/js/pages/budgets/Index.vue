<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CarFront,
    Clapperboard,
    GraduationCap,
    HeartPulse,
    House,
    PiggyBank,
    Repeat2,
    Shapes,
    ShoppingBag,
    Trash2,
    UtensilsCrossed,
    Wallet,
    Zap,
} from '@lucide/vue';
import { computed, ref, type Component } from 'vue';
import BudgetController from '@/actions/App/Http/Controllers/BudgetController';
import Heading from '@/components/Heading.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
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
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useMoney } from '@/composables/useMoney';
import { dashboard } from '@/routes';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as categoriesIndex } from '@/routes/categories';
import type { Budget, BudgetCategoryBudget, BudgetSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Presupuestos', href: budgetsIndex() },
        ],
    },
});

const props = defineProps<{
    month: string;
    budget: Budget | null;
    categories: BudgetCategoryBudget[];
    summary: BudgetSummary;
}>();

type AllocationField = {
    category_id: number;
    amount: string | number;
};

type BudgetForm = {
    month: string;
    amount: string | number;
    allocations: AllocationField[];
};

type SaveStatus = 'idle' | 'dirty' | 'saving' | 'saved' | 'error';

const { format } = useMoney();

const form = useForm<BudgetForm>({
    month: props.month,
    amount: props.budget?.amount ?? '',
    allocations: props.categories.map((category) => ({
        category_id: category.id,
        amount: category.limit,
    })),
}).transform((data) => ({
    ...(props.budget ? {} : { month: data.month }),
    amount: data.amount,
    allocations: data.allocations
        .filter((allocation) => allocation.amount !== '')
        .map((allocation) => ({
            category_id: allocation.category_id,
            amount: allocation.amount,
        })),
}));

const saveStatus = ref<SaveStatus>('idle');
let queuedAutosave: boolean | null = null;

const saveMessage = computed(() => {
    if (saveStatus.value === 'saving') {
        return 'Guardando automáticamente…';
    }

    if (saveStatus.value === 'dirty') {
        return 'Cambios sin guardar';
    }

    if (saveStatus.value === 'error') {
        return 'No se pudo guardar. Revisa los campos marcados.';
    }

    if (saveStatus.value === 'saved') {
        return 'Cambios guardados automáticamente';
    }

    return 'Los cambios se guardan al salir de cada campo';
});

const saveDotClass = computed(() => {
    if (saveStatus.value === 'saving') {
        return 'bg-amber-500 animate-pulse';
    }

    if (saveStatus.value === 'dirty') {
        return 'bg-amber-500';
    }

    if (saveStatus.value === 'error') {
        return 'bg-destructive';
    }

    if (saveStatus.value === 'saved') {
        return 'bg-emerald-500';
    }

    return 'bg-muted-foreground';
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
    const [year, month] = props.month.split('-');
    const name = monthNames[Number(month) - 1] ?? '';

    return `${name.charAt(0).toUpperCase()}${name.slice(1)} de ${year}`;
});

const allocatedExceedsBudget = computed(
    () => props.summary.allocated > props.summary.amount,
);

function currentMonth(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function navigateToMonth(month: string): void {
    if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(month) || month === props.month) {
        return;
    }

    router.visit(budgetsIndex({ query: { month } }), { preserveScroll: true });
}

function onMonthChange(value: string | number): void {
    navigateToMonth(String(value));
}

function shiftMonth(delta: number): void {
    const [year, month] = props.month.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1 + delta, 1));
    const shifted = `${date.getUTCFullYear()}-${String(
        date.getUTCMonth() + 1,
    ).padStart(2, '0')}`;

    navigateToMonth(shifted);
}

function snapshotForm(): BudgetForm {
    return {
        month: form.month,
        amount: form.amount,
        allocations: form.allocations.map((allocation) => ({
            category_id: allocation.category_id,
            amount: allocation.amount,
        })),
    };
}

function formMatches(snapshot: BudgetForm): boolean {
    return (
        form.month === snapshot.month &&
        form.amount === snapshot.amount &&
        form.allocations.length === snapshot.allocations.length &&
        form.allocations.every((allocation, index) => {
            const saved = snapshot.allocations[index];

            return (
                saved !== undefined &&
                saved.category_id === allocation.category_id &&
                saved.amount === allocation.amount
            );
        })
    );
}

function save(autosave = true): void {
    if (form.processing) {
        queuedAutosave = autosave;
        saveStatus.value = 'dirty';

        return;
    }

    if (!form.isDirty) {
        return;
    }

    const submitted = snapshotForm();

    saveStatus.value = 'saving';

    const options = {
        preserveScroll: true,
        replace: true,
        headers: autosave ? { 'X-Budget-Autosave': '1' } : undefined,
        onSuccess: () => {
            if (formMatches(submitted)) {
                form.defaults();
                saveStatus.value = 'saved';

                return;
            }

            saveStatus.value = 'dirty';
        },
        onError: () => {
            saveStatus.value = 'error';
        },
        onFinish: () => {
            if (queuedAutosave === null && formMatches(submitted)) {
                return;
            }

            const nextAutosave = queuedAutosave ?? true;

            queuedAutosave = null;

            if (form.isDirty) {
                save(nextAutosave);
            }
        },
    };

    if (props.budget) {
        form.patch(BudgetController.update.url(props.budget.id), options);

        return;
    }

    form.post(BudgetController.store.url(), options);
}

function queueSave(): void {
    if (!form.isDirty) {
        return;
    }

    saveStatus.value = 'dirty';
    save(true);
}

function onFieldBlur(event: FocusEvent): void {
    if (
        event.relatedTarget instanceof Element &&
        event.relatedTarget.closest('[data-manual-save]')
    ) {
        return;
    }

    queueSave();
}

function deleteBudget(): void {
    if (props.budget === null) {
        return;
    }

    router.delete(BudgetController.destroy(props.budget.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Presupuestos" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Presupuestos"
                description="Define tu límite mensual y reparte metas por categoría."
            />

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    size="icon-sm"
                    title="Mes anterior"
                    @click="shiftMonth(-1)"
                >
                    <ArrowLeft />
                </Button>
                <Input
                    type="month"
                    class="w-40"
                    aria-label="Mes del presupuesto"
                    :model-value="month"
                    @update:model-value="onMonthChange"
                />
                <Button
                    variant="outline"
                    size="icon-sm"
                    title="Mes siguiente"
                    @click="shiftMonth(1)"
                >
                    <ArrowRight />
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    :disabled="month === currentMonth()"
                    @click="navigateToMonth(currentMonth())"
                >
                    Mes actual
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Presupuesto</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ format(summary.amount) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Gastado</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ format(summary.spent) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Disponible</CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        class="text-2xl font-semibold tabular-nums"
                        :class="summary.available < 0 ? 'text-destructive' : ''"
                    >
                        {{ format(summary.available) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Uso del presupuesto</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ summary.usage_percent }}%
                    </p>
                    <Progress
                        :model-value="summary.usage_percent"
                        aria-label="Uso del presupuesto"
                    />
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Presupuesto de {{ monthLabel }}</CardTitle>
                <CardDescription>
                    Los límites por categoría son informativos y pueden sumar
                    más del presupuesto global.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Empty v-if="categories.length === 0">
                    <EmptyHeader>
                        <EmptyMedia variant="icon"><Wallet /></EmptyMedia>
                        <EmptyTitle>Sin categorías</EmptyTitle>
                        <EmptyDescription>
                            Crea categorías antes de repartir tu presupuesto.
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button as-child variant="outline">
                            <Link :href="categoriesIndex()">
                                Ir a categorías
                            </Link>
                        </Button>
                    </EmptyContent>
                </Empty>

                <form v-else class="grid gap-6" @submit.prevent="save(false)">
                    <FieldGroup>
                        <Field :data-invalid="!!form.errors.amount">
                            <FieldLabel for="budget-amount">
                                Presupuesto del mes
                            </FieldLabel>
                            <Input
                                id="budget-amount"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="Ej. 12000"
                                :aria-invalid="!!form.errors.amount"
                                @blur="onFieldBlur"
                                v-focus
                            />
                            <FieldDescription>
                                Monto total disponible para
                                {{ monthLabel.toLowerCase() }}.
                            </FieldDescription>
                            <FieldError v-if="form.errors.amount">
                                {{ form.errors.amount }}
                            </FieldError>
                        </Field>
                    </FieldGroup>

                    <div
                        v-if="allocatedExceedsBudget"
                        class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-500/40 bg-amber-500/10 p-3 text-sm"
                    >
                        <PiggyBank class="size-4" />
                        <span>
                            Los límites asignados suman
                            {{ format(summary.allocated) }}; superan el
                            presupuesto global, pero se guardarán igual.
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Categoría</TableHead>
                                    <TableHead>Gastado</TableHead>
                                    <TableHead>Avance</TableHead>
                                    <TableHead class="w-48">Límite</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(category, index) in categories"
                                    :key="category.id"
                                >
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-2">
                                            <component
                                                :is="iconFor(category.icon)"
                                            />
                                            <span>{{ category.name }}</span>
                                            <Badge
                                                v-if="category.user_id === null"
                                                variant="secondary"
                                            >
                                                Compartida
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="tabular-nums">
                                            {{ format(category.spent) }}
                                        </span>
                                        <span
                                            class="block text-xs text-muted-foreground"
                                        >
                                            Restante:
                                            {{ format(category.remaining) }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Progress
                                                :model-value="category.progress"
                                                class="w-20 md:w-28"
                                                :aria-label="`Avance de ${category.name}`"
                                            />
                                            <span
                                                class="w-12 text-right text-xs text-muted-foreground tabular-nums"
                                            >
                                                {{ category.progress }}%
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            v-model="
                                                form.allocations[index].amount
                                            "
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :aria-label="`Límite para ${category.name}`"
                                            :aria-invalid="
                                                !!form.errors[
                                                    `allocations.${index}.amount`
                                                ]
                                            "
                                            @blur="onFieldBlur"
                                        />
                                        <FieldError
                                            v-if="
                                                form.errors[
                                                    `allocations.${index}.amount`
                                                ]
                                            "
                                        >
                                            {{
                                                form.errors[
                                                    `allocations.${index}.amount`
                                                ]
                                            }}
                                        </FieldError>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <AlertDialog v-if="budget">
                            <AlertDialogTrigger as-child>
                                <Button variant="destructive" type="button">
                                    <Trash2 data-icon="inline-start" />
                                    Eliminar presupuesto
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>
                                        ¿Eliminar presupuesto?
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Se eliminará el presupuesto y sus
                                        límites por categoría.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>
                                        Cancelar
                                    </AlertDialogCancel>
                                    <AlertDialogAction @click="deleteBudget">
                                        Eliminar
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </form>
            </CardContent>
        </Card>

        <div
            v-if="categories.length > 0"
            class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-card/95 p-3 shadow-lg backdrop-blur"
        >
            <div
                class="flex items-center gap-2 text-sm text-muted-foreground"
                aria-live="polite"
            >
                <span class="size-2 rounded-full" :class="saveDotClass" />
                <span>{{ saveMessage }}</span>
            </div>

            <Button
                type="button"
                data-manual-save
                :disabled="form.processing || !form.isDirty"
                @mousedown.prevent
                @click="save(false)"
            >
                {{
                    form.processing
                        ? 'Guardando…'
                        : saveStatus === 'error'
                          ? 'Reintentar guardado'
                          : 'Guardar ahora'
                }}
            </Button>
        </div>
    </div>
</template>
