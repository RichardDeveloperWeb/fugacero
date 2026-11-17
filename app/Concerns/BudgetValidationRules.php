<?php

namespace App\Concerns;

use App\Models\Budget;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait BudgetValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function budgetRules(?Budget $budget = null): array
    {
        $userId = $this->user()?->getAuthIdentifier();

        $rules = [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'allocations' => ['sometimes', 'array'],
            'allocations.*.category_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(function (Builder $query) use ($userId): void {
                    $query->where(function (Builder $inner) use ($userId): void {
                        $inner->whereNull('user_id')
                            ->orWhere('user_id', $userId);
                    });
                }),
            ],
            'allocations.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ];

        if ($budget === null) {
            $rules['month'] = [
                'required',
                'string',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
                function (string $attribute, mixed $value, Closure $fail) use ($userId): void {
                    if (! is_string($value) || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
                        return;
                    }

                    [$year, $month] = explode('-', $value);

                    $exists = Budget::query()
                        ->where('user_id', $userId)
                        ->where('year', (int) $year)
                        ->where('month', (int) $month)
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un presupuesto para ese mes.');
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function budgetMessages(): array
    {
        return [
            'month.required' => 'El mes es obligatorio.',
            'month.regex' => 'El mes debe tener el formato AAAA-MM.',
            'amount.required' => 'El monto del presupuesto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número válido.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'amount.max' => 'El monto no puede superar 999999999.99.',
            'allocations.array' => 'Los límites deben ser una lista válida.',
            'allocations.*.category_id.required' => 'Selecciona una categoría.',
            'allocations.*.category_id.integer' => 'La categoría no es válida.',
            'allocations.*.category_id.distinct' => 'No puedes repetir una categoría.',
            'allocations.*.category_id.exists' => 'La categoría seleccionada no es válida.',
            'allocations.*.amount.required' => 'El límite es obligatorio.',
            'allocations.*.amount.numeric' => 'El límite debe ser un número válido.',
            'allocations.*.amount.min' => 'El límite no puede ser negativo.',
            'allocations.*.amount.max' => 'El límite es demasiado grande.',
        ];
    }
}
