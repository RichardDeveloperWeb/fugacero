<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(fn () => Date::setTestNow());

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.roles', []));
});

test('the dashboard only shows the authenticated user current month stats', function () {
    Date::setTestNow('2026-11-15 12:00:00');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $food = Category::factory()->for($user)->create([
        'name' => 'Comida',
        'color' => '#f97316',
        'icon' => 'UtensilsCrossed',
    ]);
    $transport = Category::factory()->default()->create([
        'name' => 'Transporte',
        'color' => '#3b82f6',
    ]);

    Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
        'amount' => 1000,
    ]);

    Expense::factory()->for($user)->inCategory($food)->create([
        'amount' => '120.50',
        'spent_at' => '2026-11-03',
    ]);
    Expense::factory()->for($user)->inCategory($food)->create([
        'amount' => '29.50',
        'spent_at' => '2026-11-04',
    ]);
    Expense::factory()->for($user)->inCategory($transport)->create([
        'amount' => '50',
        'spent_at' => '2026-11-05',
    ]);
    Expense::factory()->for($user)->inCategory($food)->create([
        'amount' => '900',
        'spent_at' => '2026-10-05',
    ]);
    Expense::factory()->for($other)->inCategory($food)->create([
        'amount' => '800',
        'spent_at' => '2026-11-05',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.month', '2026-11')
            ->where('stats.summary.amount', 1000)
            ->where('stats.summary.spent', 200)
            ->where('stats.summary.available', 800)
            ->where('stats.top_category.name', 'Comida')
            ->where('stats.top_category.total', 150));
});

test('the dashboard has no top category without expenses', function () {
    Date::setTestNow('2026-11-15 12:00:00');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.summary.amount', 0)
            ->where('stats.summary.spent', 0)
            ->where('stats.top_category', null));
});
