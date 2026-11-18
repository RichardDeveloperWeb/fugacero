<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return $category->user_id === null || $category->user_id === $user->id;
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user, bool $shared = false): bool
    {
        return ! $shared || $user->can('categories.create');
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category, bool $shared = false): bool
    {
        if ($this->isProtected($category)) {
            return false;
        }

        if ($category->user_id === null) {
            return $user->can('categories.update');
        }

        if ($shared) {
            return $category->user_id === $user->id && $user->can('categories.update');
        }

        return $category->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        if ($this->isProtected($category)) {
            return false;
        }

        if ($category->user_id === null) {
            return $user->can('categories.delete');
        }

        return $category->user_id === $user->id;
    }

    private function isProtected(Category $category): bool
    {
        return $category->user_id === null && $category->name === Category::UNCATEGORIZED;
    }
}
