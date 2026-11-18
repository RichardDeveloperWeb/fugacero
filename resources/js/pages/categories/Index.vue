<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import {
    CarFront,
    Clapperboard,
    GraduationCap,
    HeartPulse,
    House,
    Pencil,
    Plus,
    Repeat2,
    Shapes,
    ShoppingBag,
    Trash2,
    UtensilsCrossed,
    Zap,
} from '@lucide/vue';
import { computed, ref, type Component } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
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
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/categories';
import type { Category, User } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Categorías', href: categoriesIndex() },
        ],
    },
});

defineProps<{
    categories: Category[];
}>();

const page = usePage<{ auth: { user: User } }>();
const canCreateShared = computed(() =>
    page.props.auth.user.permissions.includes('categories.create'),
);
const canUpdateShared = computed(() =>
    page.props.auth.user.permissions.includes('categories.update'),
);
const canDeleteShared = computed(() =>
    page.props.auth.user.permissions.includes('categories.delete'),
);

const palette = [
    '#f97316',
    '#3b82f6',
    '#22c55e',
    '#a855f7',
    '#eab308',
    '#ec4899',
    '#14b8a6',
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

const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const color = ref(palette[0]);
const icon = ref('');
const shared = ref(false);

const uncategorizedName = 'Sin categoría';

const isShared = (category: Category): boolean => category.user_id === null;

const isProtected = (category: Category): boolean =>
    isShared(category) && category.name === uncategorizedName;

const canEdit = (category: Category): boolean =>
    !isProtected(category) && (!isShared(category) || canUpdateShared.value);

const canRemove = (category: Category): boolean =>
    !isProtected(category) && (!isShared(category) || canDeleteShared.value);

function openCreate(): void {
    editing.value = null;
    color.value = palette[0];
    icon.value = '';
    shared.value = false;
    dialogOpen.value = true;
}

function openEdit(category: Category): void {
    editing.value = category;
    color.value = category.color;
    icon.value = category.icon ?? '';
    shared.value = isShared(category);
    dialogOpen.value = true;
}

function onSharedChange(value: boolean | 'indeterminate'): void {
    shared.value = value === true;
}

function deleteCategory(category: Category): void {
    router.delete(CategoryController.destroy(category.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Categorías" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Categorías"
                description="Organiza tus gastos por tipo o concepto."
            />

            <Dialog v-model:open="dialogOpen">
                <DialogTrigger as-child>
                    <Button @click="openCreate">
                        <Plus data-icon="inline-start" />
                        Nueva categoría
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{
                            editing ? 'Editar categoría' : 'Nueva categoría'
                        }}</DialogTitle>
                        <DialogDescription>
                            {{
                                editing
                                    ? 'Actualiza el nombre, color o icono de la categoría.'
                                    : 'Crea una categoría personalizada para tus gastos.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        :key="editing?.id ?? 'new'"
                        v-bind="
                            editing
                                ? CategoryController.update.form(editing.id)
                                : CategoryController.store.form()
                        "
                        class="grid gap-4"
                        @success="dialogOpen = false"
                        v-slot="{ errors, processing }"
                    >
                        <FieldGroup>
                            <Field :data-invalid="!!errors.name">
                                <FieldLabel for="category-name"
                                    >Nombre</FieldLabel
                                >
                                <Input
                                    id="category-name"
                                    name="name"
                                    :default-value="editing?.name ?? ''"
                                    placeholder="Ej. Mascotas"
                                    :aria-invalid="!!errors.name"
                                    v-focus
                                />
                                <FieldError v-if="errors.name">{{
                                    errors.name
                                }}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel>Color</FieldLabel>
                                <input
                                    type="hidden"
                                    name="color"
                                    :value="color"
                                />
                                <ToggleGroup
                                    v-model="color"
                                    type="single"
                                    :spacing="2"
                                >
                                    <ToggleGroupItem
                                        v-for="option in palette"
                                        :key="option"
                                        :value="option"
                                        :aria-label="`Color ${option}`"
                                    >
                                        <span
                                            class="size-4 rounded-full"
                                            :style="{ backgroundColor: option }"
                                        />
                                    </ToggleGroupItem>
                                </ToggleGroup>
                                <FieldError v-if="errors.color">{{
                                    errors.color
                                }}</FieldError>
                            </Field>

                            <Field :data-invalid="!!errors.icon">
                                <FieldLabel for="category-icon"
                                    >Icono</FieldLabel
                                >
                                <Input
                                    id="category-icon"
                                    name="icon"
                                    :default-value="editing?.icon ?? ''"
                                    placeholder="Ej. ShoppingBag"
                                    :aria-invalid="!!errors.icon"
                                />
                                <FieldDescription>
                                    Nombre de un icono de Lucide. Si lo dejas
                                    vacío se usará uno genérico.
                                </FieldDescription>
                                <FieldError v-if="errors.icon">{{
                                    errors.icon
                                }}</FieldError>
                            </Field>

                            <Field
                                v-if="
                                    editing ? canUpdateShared : canCreateShared
                                "
                            >
                                <div class="flex items-start gap-3">
                                    <Checkbox
                                        id="category-shared"
                                        :model-value="shared"
                                        :disabled="
                                            editing !== null &&
                                            isShared(editing)
                                        "
                                        class="mt-0.5"
                                        @update:model-value="onSharedChange"
                                    />
                                    <input
                                        type="hidden"
                                        name="shared"
                                        :value="shared ? '1' : '0'"
                                    />
                                    <div class="grid gap-1">
                                        <FieldLabel
                                            for="category-shared"
                                            class="leading-none"
                                            >Compartida</FieldLabel
                                        >
                                        <FieldDescription>
                                            Disponible para todos los usuarios.
                                        </FieldDescription>
                                    </div>
                                </div>
                                <FieldError v-if="errors.shared">{{
                                    errors.shared
                                }}</FieldError>
                            </Field>
                        </FieldGroup>

                        <DialogFooter>
                            <Button type="submit" :disabled="processing">
                                {{ processing ? 'Guardando…' : 'Guardar' }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Listado de categorías</CardTitle>
            </CardHeader>
            <CardContent>
                <Empty v-if="categories.length === 0">
                    <EmptyHeader>
                        <EmptyMedia variant="icon"><Shapes /></EmptyMedia>
                        <EmptyTitle>Sin categorías</EmptyTitle>
                        <EmptyDescription
                            >Crea tu primera categoría para
                            empezar.</EmptyDescription
                        >
                    </EmptyHeader>
                    <EmptyContent>
                        <Button @click="openCreate">Crear categoría</Button>
                    </EmptyContent>
                </Empty>

                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Color</TableHead>
                            <TableHead>Gastos</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead class="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="category in categories"
                            :key="category.id"
                        >
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-2">
                                    <component :is="iconFor(category.icon)" />
                                    {{ category.name }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <span
                                    class="inline-block size-4 rounded-full border"
                                    :style="{ backgroundColor: category.color }"
                                    :aria-label="category.color"
                                />
                            </TableCell>
                            <TableCell>{{ category.expenses_count }}</TableCell>
                            <TableCell>
                                <Badge
                                    v-if="isShared(category)"
                                    variant="secondary"
                                    >Compartida</Badge
                                >
                                <Badge v-else variant="outline">Personal</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div
                                    v-if="
                                        canEdit(category) || canRemove(category)
                                    "
                                    class="flex justify-end gap-2"
                                >
                                    <Button
                                        v-if="canEdit(category)"
                                        variant="ghost"
                                        size="icon-sm"
                                        title="Editar"
                                        @click="openEdit(category)"
                                    >
                                        <Pencil />
                                        <span class="sr-only"
                                            >Editar {{ category.name }}</span
                                        >
                                    </Button>

                                    <AlertDialog v-if="canRemove(category)">
                                        <AlertDialogTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                title="Eliminar"
                                            >
                                                <Trash2 />
                                                <span class="sr-only"
                                                    >Eliminar
                                                    {{ category.name }}</span
                                                >
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle
                                                    >¿Eliminar
                                                    categoría?</AlertDialogTitle
                                                >
                                                <AlertDialogDescription>
                                                    Se eliminará «{{
                                                        category.name
                                                    }}». Esta acción no se puede
                                                    deshacer.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel
                                                    >Cancelar</AlertDialogCancel
                                                >
                                                <AlertDialogAction
                                                    @click="
                                                        deleteCategory(category)
                                                    "
                                                >
                                                    Eliminar
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                                <span
                                    v-else
                                    class="text-sm text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
