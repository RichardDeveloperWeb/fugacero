<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds roles, default categories and demo expenses', function () {
    $existingUserIds = User::query()->pluck('id')->all();

    $this->seed();

    $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();
    $newUsers = User::query()->whereNotIn('id', $existingUserIds)->get();

    expect(Role::query()->whereIn('name', ['admin', 'user'])->count())->toBe(2)
        ->and(Permission::query()->whereIn('name', [
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
        ])->count())->toBe(4)
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->getAllPermissions()->pluck('name')->values()->all())->toEqualCanonicalizing([
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
        ])
        ->and(User::role('user')->firstOrFail()->getAllPermissions()->isEmpty())->toBeTrue()
        ->and($newUsers->filter(fn (User $user) => $user->hasRole('user'))->count())->toBe(8)
        ->and(Category::query()->whereNull('user_id')->where('name', Category::UNCATEGORIZED)->exists())->toBeTrue()
        ->and($admin->expenses()->count())->toBe(18)
        ->and(Expense::query()
            ->whereIn('user_id', $newUsers->pluck('id')->all())
            ->whereHas('user', fn ($query) => $query->role('user'))
            ->count())->toBe(48)
        ->and(Expense::query()->whereNull('category_id')->count())->toBe(0)
        ->and(Expense::query()->distinct()->count('category_id'))->toBeGreaterThan(1);
});

it('assigns seeded roles and permissions to existing users', function () {
    $admin = User::factory()->create(['email' => 'admin@gmail.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    $this->seed(RoleSeeder::class);

    $admin->refresh();
    $user->refresh();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->getAllPermissions()->pluck('name')->values()->all())->toEqualCanonicalizing([
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
        ])
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->getAllPermissions()->isEmpty())->toBeTrue();
});
