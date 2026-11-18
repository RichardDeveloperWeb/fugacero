<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * @return Collection<int, Category>
     */
    public function listFor(User $user): Collection
    {
        return Category::query()
            ->withCount('expenses')
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Category
    {
        $shared = (bool) data_get($attributes, 'shared', false);

        unset($attributes['shared']);

        return Category::query()->create([
            ...$attributes,
            'user_id' => $shared ? null : $user->id,
            'is_default' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Category $category, array $attributes): Category
    {
        $shared = array_key_exists('shared', $attributes)
            ? (bool) $attributes['shared']
            : $category->user_id === null;

        unset($attributes['shared']);

        $category->update([
            ...$attributes,
            'user_id' => $shared ? null : $category->user_id,
        ]);

        return $category;
    }

    public function delete(Category $category): bool
    {
        if ($category->expenses()->exists() || $category->budgetCategories()->exists()) {
            return false;
        }

        return $category->delete();
    }
}
