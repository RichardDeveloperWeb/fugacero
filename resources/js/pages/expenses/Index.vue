<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ArrowLeft,
    ArrowRight,
    CarFront,
    Clapperboard,
    GraduationCap,
    HeartPulse,
    House,
    Pencil,
    Plus,
    Receipt,
    Repeat2,
    Search,
    Shapes,
    ShoppingBag,
    Trash2,
    UtensilsCrossed,
    Zap,
} from '@lucide/vue';
import { computed, ref, watch, type Component } from 'vue';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
import ExpenseFormFields from '@/components/ExpenseFormFields.vue';
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
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
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
import { index as expensesIndex } from '@/routes/expenses';
import type {
    Category,
    Expense,
    ExpenseFilters,
    ExpenseTypeValue,
    Pagination,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Gastos', href: expensesIndex() },
        ],
    },
});

const props = defineProps<{
    expenses: Pagination<Expense>;
    categories: Category[];
    filters: ExpenseFilters;
}>();

const { format } = useMoney();

const selectClass =
    'border-input bg-background text-foreground h-9 w-full min-w-0 rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const typeOptions: Array<{ value: ExpenseTypeValue; label: string }> = [
    { value: 'expense', label: 'Gasto' },
    { value: 'subscription', label: 'Suscripción' },
    { value: 'ant_expense', label: 'Gasto hormiga' },
];

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

const typeLabel = (value: ExpenseTypeValue): string =>
    typeOptions.find((option) => option.value === value)?.label ?? value;

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    timeZone: 'UTC',
});

function formatDate(value: string): string {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
}

function today(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function currentMonth(): string {
    return today().slice(0, 7);
}

const month = ref(props.filters.month ?? currentMonth());
const type = ref<ExpenseTypeValue | ''>(props.filters.type ?? '');
const categoryId = ref(
    props.filters.category_id ? String(props.filters.category_id) : '',
);
const search = ref(props.filters.search ?? '');

watch(
    () => props.filters,
    (filters) => {
        month.value = filters.month ?? currentMonth();
        type.value = filters.type ?? '';
        categoryId.value = filters.category_id
            ? String(filters.category_id)
            : '';
        search.value = filters.search ?? '';
    },
);

type ExpenseQuery = {
    month?: string;
    type?: ExpenseTypeValue;
    category_id?: number;
    search?: string;
    page?: number;
};

function currentQuery(): ExpenseQuery {
    const query: ExpenseQuery = {
        month: month.value || currentMonth(),
    };

    if (type.value) {
        query.type = type.value;
    }

    if (categoryId.value) {
        query.category_id = Number(categoryId.value);
    }

    if (search.value.trim()) {
        query.search = search.value.trim();
    }

    return query;
}

function visit(query: ExpenseQuery): void {
    router.visit(expensesIndex({ query }), {
        preserveScroll: true,
        preserveState: true,
    });
}

function applyFilters(): void {
    if (!month.value) {
        month.value = currentMonth();
    }

    visit(currentQuery());
}

const debouncedApplyFilters = useDebounceFn(() => applyFilters(), 300);

function onMonthChange(value: string | number): void {
    month.value = String(value);
    applyFilters();
}

function onSearchChange(value: string | number): void {
    search.value = String(value);
    debouncedApplyFilters();
}

function clearFilters(): void {
    month.value = currentMonth();
    type.value = '';
    categoryId.value = '';
    search.value = '';
    applyFilters();
}

function visitPage(page: number): void {
    visit({ ...currentQuery(), page });
}

const pageLabel = computed(
    () =>
        `Página ${props.expenses.current_page} de ${props.expenses.last_page}`,
);

const uncategorizedCategoryId = computed(
    () =>
        props.categories.find((category) => category.name === 'Sin categoría')
            ?.id,
);

const dialogOpen = ref(false);
const editing = ref<Expense | null>(null);
const formDescription = ref('');
const formAmount = ref('');
const formType = ref<ExpenseTypeValue>('expense');
const formCategoryId = ref('');
const formSpentAt = ref(today());

function openCreate(): void {
    editing.value = null;
    formDescription.value = '';
    formAmount.value = '';
    formType.value = 'expense';
    formCategoryId.value = uncategorizedCategoryId.value
        ? String(uncategorizedCategoryId.value)
        : '';
    formSpentAt.value =
        props.filters.month && props.filters.month !== currentMonth()
            ? `${props.filters.month}-01`
            : today();
    dialogOpen.value = true;
}

function openEdit(expense: Expense): void {
    editing.value = expense;
    formDescription.value = expense.description;
    formAmount.value = expense.amount;
    formType.value = expense.type;
    formCategoryId.value = String(expense.category_id);
    formSpentAt.value = expense.spent_at.slice(0, 10);
    dialogOpen.value = true;
}

function deleteExpense(expense: Expense): void {
    router.delete(ExpenseController.destroy(expense.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Gastos" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Gastos"
                description="Registra gastos, suscripciones y compras pequeñas."
            />

            <Dialog v-model:open="dialogOpen">
                <DialogTrigger as-child>
                    <Button @click="openCreate">
                        <Plus data-icon="inline-start" />
                        Nuevo gasto
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {{ editing ? 'Editar gasto' : 'Nuevo gasto' }}
                        </DialogTitle>
                        <DialogDescription>
                            {{
                                editing
                                    ? 'Actualiza los detalles del gasto.'
                                    : 'Agrega un movimiento a tu control de gastos.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        :key="editing?.id ?? 'new'"
                        v-bind="
                            editing
                                ? ExpenseController.update.form(editing.id)
                                : ExpenseController.store.form()
                        "
                        class="grid gap-4"
                        @success="dialogOpen = false"
                        v-slot="{ errors, processing }"
                    >
                        <ExpenseFormFields
                            v-model:description="formDescription"
                            v-model:amount="formAmount"
                            v-model:type="formType"
                            v-model:category-id="formCategoryId"
                            v-model:spent-at="formSpentAt"
                            :categories="categories"
                            :errors="errors"
                        />

                        <DialogFooter>
                            <Button type="submit" :disabled="processing">
                                {{
                                    processing
                                        ? 'Guardando…'
                                        : editing
                                          ? 'Guardar cambios'
                                          : 'Crear gasto'
                                }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardHeader class="pb-4">
                <CardTitle>Filtros</CardTitle>
            </CardHeader>
            <CardContent>
                <form
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"
                    @submit.prevent="applyFilters"
                >
                    <Input
                        type="month"
                        aria-label="Mes del gasto"
                        class="w-full"
                        :model-value="month"
                        @update:model-value="onMonthChange"
                    />
                    <select
                        v-model="type"
                        :class="selectClass"
                        aria-label="Tipo de gasto"
                        @change="applyFilters"
                    >
                        <option value="">Todos los tipos</option>
                        <option
                            v-for="option in typeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <select
                        v-model="categoryId"
                        :class="selectClass"
                        aria-label="Categoría del gasto"
                        @change="applyFilters"
                    >
                        <option value="">Todas las categorías</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <Input
                        type="search"
                        placeholder="Buscar gasto…"
                        aria-label="Buscar gasto"
                        :model-value="search"
                        @update:model-value="onSearchChange"
                    />
                    <div class="flex gap-2">
                        <Button type="submit" class="flex-1">
                            <Search data-icon="inline-start" />
                            Buscar
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            @click="clearFilters"
                        >
                            Limpiar
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Listado de gastos</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <Empty v-if="expenses.data.length === 0">
                    <EmptyHeader>
                        <EmptyMedia variant="icon"><Receipt /></EmptyMedia>
                        <EmptyTitle>Sin gastos</EmptyTitle>
                        <EmptyDescription>
                            Ajusta los filtros o registra tu primer gasto.
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button @click="openCreate">Crear gasto</Button>
                    </EmptyContent>
                </Empty>

                <div v-else class="overflow-x-auto rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead class="text-right">Monto</TableHead>
                                <TableHead class="text-right">
                                    Acciones
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="expense in expenses.data"
                                :key="expense.id"
                            >
                                <TableCell class="whitespace-nowrap">
                                    {{ formatDate(expense.spent_at) }}
                                </TableCell>
                                <TableCell class="font-medium">
                                    {{ expense.description }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <component
                                            :is="
                                                iconFor(
                                                    expense.category?.icon ??
                                                        null,
                                                )
                                            "
                                            class="size-4"
                                        />
                                        <span>
                                            {{
                                                expense.category?.name ??
                                                'Sin categoría'
                                            }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            expense.type === 'expense'
                                                ? 'outline'
                                                : 'secondary'
                                        "
                                    >
                                        {{ typeLabel(expense.type) }}
                                    </Badge>
                                </TableCell>
                                <TableCell
                                    class="text-right font-medium tabular-nums"
                                >
                                    {{ format(expense.amount) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            title="Editar"
                                            @click="openEdit(expense)"
                                        >
                                            <Pencil />
                                            <span class="sr-only">
                                                Editar {{ expense.description }}
                                            </span>
                                        </Button>

                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    title="Eliminar"
                                                >
                                                    <Trash2 />
                                                    <span class="sr-only">
                                                        Eliminar
                                                        {{
                                                            expense.description
                                                        }}
                                                    </span>
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>
                                                        ¿Eliminar gasto?
                                                    </AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Se eliminará «{{
                                                            expense.description
                                                        }}». Esta acción no se
                                                        puede deshacer.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>
                                                        Cancelar
                                                    </AlertDialogCancel>
                                                    <AlertDialogAction
                                                        @click="
                                                            deleteExpense(
                                                                expense,
                                                            )
                                                        "
                                                    >
                                                        Eliminar
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div
                    v-if="expenses.last_page > 1"
                    class="flex flex-wrap items-center justify-between gap-3"
                >
                    <p class="text-sm text-muted-foreground">
                        Mostrando {{ expenses.from ?? 0 }}–{{
                            expenses.to ?? 0
                        }}
                        de {{ expenses.total }} · {{ pageLabel }}
                    </p>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!expenses.prev_page_url"
                            @click="visitPage(expenses.current_page - 1)"
                        >
                            <ArrowLeft />
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!expenses.next_page_url"
                            @click="visitPage(expenses.current_page + 1)"
                        >
                            Siguiente
                            <ArrowRight />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
