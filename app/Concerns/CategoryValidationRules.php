<?php

namespace App\Concerns;

use App\Models\Category;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait CategoryValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function categoryRules(?Category $category = null): array
    {
        $userId = $this->user()?->getAuthIdentifier();

        $uniqueName = Rule::unique('categories', 'name')
            ->where(function (Builder $query) use ($userId): void {
                $query->where(function (Builder $inner) use ($userId): void {
                    $inner->whereNull('user_id')
                        ->orWhere('user_id', $userId);
                });
            });

        if ($category !== null) {
            $uniqueName->ignore($category->id);
        }

        return [
            'name' => ['required', 'string', 'max:80', $uniqueName],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z]+$/'],
            'shared' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function categoryMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
            'color.required' => 'El color es obligatorio.',
            'color.regex' => 'El color debe ser un valor hexadecimal como #f97316.',
            'icon.regex' => 'El icono solo puede contener letras.',
        ];
    }
}
