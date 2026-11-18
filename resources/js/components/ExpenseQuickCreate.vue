<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
import ExpenseFormFields from '@/components/ExpenseFormFields.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Category, ExpenseTypeValue } from '@/types';

const props = defineProps<{
    categories: Category[];
}>();

const open = defineModel<boolean>('open');

const formKey = ref(0);
const description = ref('');
const amount = ref('');
const type = ref<ExpenseTypeValue>('expense');
const categoryId = ref('');
const spentAt = ref('');

const uncategorizedCategoryId = computed(
    () =>
        props.categories.find((category) => category.name === 'Sin categoría')
            ?.id,
);

function today(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function reset(): void {
    description.value = '';
    amount.value = '';
    type.value = 'expense';
    categoryId.value = uncategorizedCategoryId.value
        ? String(uncategorizedCategoryId.value)
        : '';
    spentAt.value = today();
    formKey.value += 1;
}

watch(open, (isOpen) => {
    if (isOpen) {
        reset();
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Nuevo gasto</DialogTitle>
                <DialogDescription>
                    Registra un movimiento sin salir del inicio.
                </DialogDescription>
            </DialogHeader>

            <Form
                :key="formKey"
                v-bind="ExpenseController.storeFromDashboard.form()"
                class="grid gap-4"
                @success="open = false"
                v-slot="{ errors, processing }"
            >
                <ExpenseFormFields
                    v-model:description="description"
                    v-model:amount="amount"
                    v-model:type="type"
                    v-model:category-id="categoryId"
                    v-model:spent-at="spentAt"
                    :categories="categories"
                    :errors="errors"
                    id-prefix="quick-expense"
                />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="processing"
                        @click="open = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Guardando…' : 'Crear gasto' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
