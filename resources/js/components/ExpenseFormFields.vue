<script setup lang="ts">
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { Category, ExpenseTypeValue } from '@/types';

type Props = {
    categories: Category[];
    errors: Record<string, string>;
    idPrefix?: string;
};

withDefaults(defineProps<Props>(), {
    idPrefix: 'expense',
});

const description = defineModel<string>('description');
const amount = defineModel<string>('amount');
const type = defineModel<ExpenseTypeValue>('type');
const categoryId = defineModel<string>('categoryId');
const spentAt = defineModel<string>('spentAt');

const selectClass =
    'border-input bg-background text-foreground h-9 w-full min-w-0 rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const typeOptions: Array<{ value: ExpenseTypeValue; label: string }> = [
    { value: 'expense', label: 'Gasto' },
    { value: 'subscription', label: 'Suscripción' },
    { value: 'ant_expense', label: 'Gasto hormiga' },
];
</script>

<template>
    <FieldGroup>
        <Field :data-invalid="!!errors.description">
            <FieldLabel :for="`${idPrefix}-description`">
                Descripción
            </FieldLabel>
            <Input
                :id="`${idPrefix}-description`"
                v-model="description"
                name="description"
                placeholder="Ej. Desayuno"
                :aria-invalid="!!errors.description"
                v-focus
            />
            <FieldError v-if="errors.description">
                {{ errors.description }}
            </FieldError>
        </Field>

        <div class="grid gap-4 sm:grid-cols-2">
            <Field :data-invalid="!!errors.amount">
                <FieldLabel :for="`${idPrefix}-amount`">Monto</FieldLabel>
                <Input
                    :id="`${idPrefix}-amount`"
                    v-model="amount"
                    name="amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    :aria-invalid="!!errors.amount"
                />
                <FieldError v-if="errors.amount">
                    {{ errors.amount }}
                </FieldError>
            </Field>

            <Field :data-invalid="!!errors.spent_at">
                <FieldLabel :for="`${idPrefix}-spent-at`">Fecha</FieldLabel>
                <Input
                    :id="`${idPrefix}-spent-at`"
                    v-model="spentAt"
                    name="spent_at"
                    type="date"
                    :aria-invalid="!!errors.spent_at"
                />
                <FieldError v-if="errors.spent_at">
                    {{ errors.spent_at }}
                </FieldError>
            </Field>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <Field :data-invalid="!!errors.type">
                <FieldLabel :for="`${idPrefix}-type`">Tipo</FieldLabel>
                <select
                    :id="`${idPrefix}-type`"
                    v-model="type"
                    name="type"
                    :class="selectClass"
                    :aria-invalid="!!errors.type"
                >
                    <option
                        v-for="option in typeOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <FieldError v-if="errors.type">
                    {{ errors.type }}
                </FieldError>
            </Field>

            <Field :data-invalid="!!errors.category_id">
                <FieldLabel :for="`${idPrefix}-category`">Categoría</FieldLabel>
                <select
                    :id="`${idPrefix}-category`"
                    v-model="categoryId"
                    name="category_id"
                    :class="selectClass"
                    :aria-invalid="!!errors.category_id"
                >
                    <option value="" disabled>Selecciona una categoría</option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="String(category.id)"
                    >
                        {{ category.name }}
                    </option>
                </select>
                <FieldError v-if="errors.category_id">
                    {{ errors.category_id }}
                </FieldError>
            </Field>
        </div>
    </FieldGroup>
</template>
