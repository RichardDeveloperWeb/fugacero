<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function adminDashboardUser(): User
{
    $admin = User::factory()->create();
    Role::findOrCreate('admin', 'web');
    $admin->assignRole('admin');

    return $admin;
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('users without the admin role cannot visit the admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admins see global metrics and historical top categories', function () {
    $admin = adminDashboardUser();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $food = Category::factory()->default()->create([
        'name' => 'Comida',
        'color' => '#f97316',
    ]);
    $transport = Category::factory()->default()->create([
        'name' => 'Transporte',
        'color' => '#3b82f6',
    ]);

    Expense::factory()->for($admin)->inCategory($food)->create(['amount' => '100']);
    Expense::factory()->for($user)->inCategory($food)->create(['amount' => '300']);
    Expense::factory()->for($user)->inCategory($transport)->create(['amount' => '100']);
    Expense::factory()->for($other)->inCategory($transport)->create(['amount' => '200']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('auth.user.roles', ['admin'])
            ->where('stats.users', 3)
            ->where('stats.users_with_expenses', 3)
            ->where('stats.total_spent', 700)
            ->where('stats.average_per_user', 233.33)
            ->where('stats.top_categories.0.name', 'Comida')
            ->where('stats.top_categories.0.total', 400)
            ->where('stats.top_categories.0.expenses_count', 2)
            ->where('stats.top_categories.1.name', 'Transporte')
            ->where('stats.top_categories.1.total', 300));
});

test('admins see empty top categories when there are no expenses', function () {
    $admin = adminDashboardUser();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('stats.users', 1)
            ->where('stats.users_with_expenses', 0)
            ->where('stats.total_spent', 0)
            ->where('stats.average_per_user', 0)
            ->where('stats.top_categories', []));
});
