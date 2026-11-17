<?php

namespace App\Concerns;

use App\ExpenseType;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait ExpenseValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function expenseRules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();

        return [
            'description' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'type' => ['required', Rule::enum(ExpenseType::class)],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function (Builder $query) use ($userId): void {
                    $query->where(function (Builder $inner) use ($userId): void {
                        $inner->whereNull('user_id')
                            ->orWhere('user_id', $userId);
                    });
                }),
            ],
            'spent_at' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function expenseMessages(): array
    {
        return [
            'description.required' => 'La descripción es obligatoria.',
            'description.max' => 'La descripción no puede superar 150 caracteres.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número válido.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'amount.max' => 'El monto no puede superar 999999999.99.',
            'type.required' => 'El tipo es obligatorio.',
            'type.enum' => 'El tipo seleccionado no es válido.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.integer' => 'La categoría no es válida.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'spent_at.required' => 'La fecha es obligatoria.',
            'spent_at.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
        ];
    }
}
