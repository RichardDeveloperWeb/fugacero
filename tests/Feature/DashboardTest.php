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
            ->where('stats.top_category.total', 150)
            ->count('charts.monthly_spending.labels', 6)
            ->where('charts.monthly_spending.labels.4', '2026-10')
            ->where('charts.monthly_spending.spent.4', 900)
            ->where('charts.monthly_spending.spent.5', 200)
            ->where('charts.monthly_spending.budget.5', 1000)
            ->where('charts.category_breakdown.categories.0.name', 'Comida')
            ->where('charts.category_breakdown.categories.0.total', 150)
            ->where('charts.category_breakdown.categories.0.expenses_count', 2)
            ->where('charts.category_breakdown.categories.1.name', 'Transporte')
            ->where('charts.category_breakdown.other_total', 0)
            ->count('charts.monthly_pace.labels', 30)
            ->where('charts.monthly_pace.cumulative.4', 200)
            ->where('charts.monthly_pace.ideal.14', 500)
            ->where('charts.monthly_pace.budget', 1000)
            ->where('charts.monthly_pace.days_elapsed', 15));
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
            ->where('stats.top_category', null)
            ->where('charts.monthly_spending.spent', array_fill(0, 6, 0))
            ->where('charts.monthly_spending.budget', array_fill(0, 6, 0))
            ->where('charts.category_breakdown.categories', [])
            ->where('charts.category_breakdown.other_total', 0)
            ->where('charts.monthly_pace.budget', 0)
            ->where('charts.monthly_pace.days_elapsed', 15));
});

test('the category chart keeps the top five and groups the rest', function () {
    Date::setTestNow('2026-11-15 12:00:00');

    $user = User::factory()->create();

    foreach (range(1, 6) as $number) {
        $category = Category::factory()->default()->create([
            'name' => "Categoría {$number}",
            'color' => '#3b82f6',
        ]);

        Expense::factory()->for($user)->inCategory($category)->create([
            'amount' => $number * 100,
            'spent_at' => '2026-11-10',
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->count('charts.category_breakdown.categories', 5)
            ->where('charts.category_breakdown.categories.0.name', 'Categoría 6')
            ->where('charts.category_breakdown.categories.0.total', 600)
            ->where('charts.category_breakdown.categories.4.name', 'Categoría 2')
            ->where('charts.category_breakdown.categories.4.total', 200)
            ->where('charts.category_breakdown.other_total', 100));
});
