<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds roles, default categories and demo expenses', function () {
    $this->seed();

    $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();

    expect(Role::query()->whereIn('name', ['admin', 'user'])->count())->toBe(2)
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and(User::role('user')->count())->toBe(8)
        ->and(Category::query()->whereNull('user_id')->where('name', Category::UNCATEGORIZED)->exists())->toBeTrue()
        ->and($admin->expenses()->count())->toBe(18)
        ->and(Expense::query()->whereHas('user', fn ($query) => $query->role('user'))->count())->toBe(48)
        ->and(Expense::query()->whereNull('category_id')->count())->toBe(0)
        ->and(Expense::query()->distinct()->count('category_id'))->toBeGreaterThan(1);
});
