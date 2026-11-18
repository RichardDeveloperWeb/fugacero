<?php

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('allows only owners to manage their budgets', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $own = Budget::factory()->for($owner)->create();
    $foreign = Budget::factory()->for($other)->create();

    expect(Gate::forUser($owner)->allows('viewAny', Budget::class))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('create', Budget::class))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $foreign))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('update', $foreign))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $foreign))->toBeFalse();
});

it('builds the month summary without per-category or standalone expense queries', function () {
    $user = User::factory()->create();
    $own = Category::factory()->for($user)->create();
    $shared = Category::factory()->default()->create();
    $uncategorized = Category::uncategorized();
    $budget = Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
        'amount' => 5000,
    ]);

    BudgetCategory::factory()->for($budget)->for($own, 'category')->create(['amount' => 2000]);

    Expense::factory()->for($user)->for($own, 'category')->create([
        'amount' => '150.50',
        'spent_at' => '2026-11-05',
    ]);
    Expense::factory()->for($user)->for($own, 'category')->create([
        'amount' => '99.50',
        'spent_at' => '2026-11-20',
    ]);
    Expense::factory()->for($user)->for($uncategorized, 'category')->create([
        'amount' => '50',
        'spent_at' => '2026-11-08',
    ]);
    Expense::factory()->for($user)->for($own, 'category')->create([
        'amount' => '800',
        'spent_at' => '2026-10-20',
    ]);
    Expense::factory()->for(User::factory())->for($own, 'category')->create([
        'amount' => '500',
        'spent_at' => '2026-11-20',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $summary = app(BudgetService::class)->summaryFor($user, '2026-11');

    DB::disableQueryLog();

    $queries = collect(DB::getQueryLog())->pluck('query');

    expect($queries->filter(fn (string $sql) => str_contains($sql, 'from "categories"')))->toHaveCount(1)
        ->and($queries->filter(fn (string $sql) => str_contains($sql, 'from "expenses"') && ! str_contains($sql, 'from "categories"')))->toHaveCount(0)
        ->and($summary['categories'])->toHaveCount(3)
        ->and(collect($summary['categories'])->firstWhere('id', $own->id)['spent'])->toBe(250.0)
        ->and(collect($summary['categories'])->firstWhere('id', $own->id)['limit'])->toBe(2000.0)
        ->and(collect($summary['categories'])->firstWhere('id', $shared->id)['spent'])->toBe(0.0)
        ->and(collect($summary['categories'])->firstWhere('id', $uncategorized->id)['spent'])->toBe(50.0)
        ->and($summary['summary']['spent'])->toBe(300.0)
        ->and($summary['summary']['allocated'])->toBe(2000.0);
});

it('counts uncategorized expenses toward the monthly budget', function () {
    $user = User::factory()->create();
    $uncategorized = Category::uncategorized();
    $budget = Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
        'amount' => 1000,
    ]);

    BudgetCategory::factory()->for($budget)->for($uncategorized, 'category')->create(['amount' => 300]);
    Expense::factory()->for($user)->for($uncategorized, 'category')->create([
        'amount' => '120.50',
        'spent_at' => '2026-11-05',
    ]);
    Expense::factory()->for(User::factory())->for($uncategorized, 'category')->create([
        'amount' => '900',
        'spent_at' => '2026-11-06',
    ]);

    $summary = app(BudgetService::class)->summaryFor($user, '2026-11');

    expect(collect($summary['categories'])->firstWhere('id', $uncategorized->id)['spent'])->toBe(120.5)
        ->and($summary['summary']['spent'])->toBe(120.5)
        ->and($summary['summary']['allocated'])->toBe(300.0)
        ->and($summary['summary']['available'])->toBe(879.5);
});

it('only loads the budget for the requested month', function () {
    $user = User::factory()->create();
    $november = Budget::factory()->for($user)->create(['year' => 2026, 'month' => 11]);
    $december = Budget::factory()->for($user)->create(['year' => 2026, 'month' => 12]);

    $service = app(BudgetService::class);

    expect($service->summaryFor($user, '2026-11')['budget']->id)->toBe($november->id)
        ->and($service->summaryFor($user, '2026-12')['budget']->id)->toBe($december->id)
        ->and($service->summaryFor($user, '2027-01')['budget'])->toBeNull();
});

it('redirects guests to the login page', function () {
    $this->get(route('budgets.index'))->assertRedirect(route('login'));
    $this->post(route('budgets.store'), [])->assertRedirect(route('login'));
});

it('exposes the month summary to the budgets page', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $uncategorized = Category::uncategorized();
    $budget = Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
        'amount' => 5000,
    ]);

    BudgetCategory::factory()->for($budget)->for($category, 'category')->create(['amount' => 1000]);
    Expense::factory()->for($user)->for($category, 'category')->create([
        'amount' => 250,
        'spent_at' => '2026-11-10',
    ]);

    $this->actingAs($user)
        ->get(route('budgets.index', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('month', '2026-11')
            ->where('budget.id', $budget->id)
            ->has('categories', 2)
            ->where('categories.0.id', $uncategorized->id)
            ->where('categories.0.user_id', null)
            ->where('categories.0.limit', 0)
            ->where('categories.0.spent', 0)
            ->where('categories.1.id', $category->id)
            ->where('categories.1.user_id', $user->id)
            ->where('categories.1.limit', 1000)
            ->where('categories.1.spent', 250)
            ->where('summary.amount', 5000)
            ->where('summary.spent', 250)
            ->where('summary.available', 4750)
            ->where('summary.allocated', 1000)
            ->where('summary.usage_percent', 5));
});

it('validates the budget payload', function () {
    $user = User::factory()->create();
    $shared = Category::factory()->default()->create();
    $foreign = Category::factory()->for(User::factory())->create();

    Budget::factory()->for($user)->create(['year' => 2026, 'month' => 11]);

    $this->actingAs($user)
        ->post(route('budgets.store'), [])
        ->assertSessionHasErrors(['month', 'amount']);

    $this->actingAs($user)
        ->post(route('budgets.store'), ['month' => '11/2026', 'amount' => 100])
        ->assertSessionHasErrors(['month' => 'El mes debe tener el formato AAAA-MM.']);

    $this->actingAs($user)
        ->post(route('budgets.store'), ['month' => '2026-11', 'amount' => 100])
        ->assertSessionHasErrors(['month' => 'Ya existe un presupuesto para ese mes.']);

    $this->actingAs($user)
        ->post(route('budgets.store'), [
            'month' => '2026-12',
            'amount' => 100,
            'allocations' => [['category_id' => $foreign->id, 'amount' => 10]],
        ])
        ->assertSessionHasErrors(['allocations.0.category_id']);

    $this->actingAs($user)
        ->post(route('budgets.store'), [
            'month' => '2026-12',
            'amount' => 100,
            'allocations' => [
                ['category_id' => $shared->id, 'amount' => 10],
                ['category_id' => $shared->id, 'amount' => 20],
            ],
        ])
        ->assertSessionHasErrors(['allocations.1.category_id']);

    $this->actingAs($user)
        ->post(route('budgets.store'), [
            'month' => '2026-12',
            'amount' => 100,
            'allocations' => [['category_id' => $shared->id, 'amount' => -1]],
        ])
        ->assertSessionHasErrors(['allocations.0.amount' => 'El límite no puede ser negativo.']);
});

it('creates a budget with its category allocations', function () {
    $user = User::factory()->create();
    $shared = Category::factory()->default()->create();
    $own = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('budgets.store'), [
            'month' => '2026-11',
            'amount' => '5000',
            'allocations' => [
                ['category_id' => $shared->id, 'amount' => '2000'],
                ['category_id' => $own->id, 'amount' => '500'],
            ],
        ])
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']));

    $budget = Budget::query()
        ->where('user_id', $user->id)
        ->where('year', 2026)
        ->where('month', 11)
        ->firstOrFail();

    expect($budget->amount)->toBe('5000.00')
        ->and($budget->categories)->toHaveCount(2);

    $this->assertDatabaseHas('budget_categories', [
        'budget_id' => $budget->id,
        'category_id' => $shared->id,
        'amount' => '2000.00',
    ])->assertDatabaseHas('budget_categories', [
        'budget_id' => $budget->id,
        'category_id' => $own->id,
        'amount' => '500.00',
    ]);
});

it('allows category limits to exceed the global budget', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('budgets.store'), [
            'month' => '2026-11',
            'amount' => '1000',
            'allocations' => [['category_id' => $category->id, 'amount' => '9000']],
        ])
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']));

    $budget = Budget::query()->where('user_id', $user->id)->firstOrFail();

    $this->assertDatabaseHas('budget_categories', [
        'budget_id' => $budget->id,
        'category_id' => $category->id,
        'amount' => '9000.00',
    ]);
});

it('updates the amount and synchronizes category allocations', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
        'amount' => 5000,
    ]);
    $old = Category::factory()->for($user)->create();
    $new = Category::factory()->for($user)->create();

    BudgetCategory::factory()->for($budget)->for($old, 'category')->create(['amount' => 100]);

    $this->actingAs($user)
        ->patch(route('budgets.update', $budget), [
            'amount' => '6000',
            'allocations' => [['category_id' => $new->id, 'amount' => '700']],
        ])
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']));

    expect($budget->refresh()->amount)->toBe('6000.00')
        ->and($budget->categories)->toHaveCount(1);

    $this->assertDatabaseMissing('budget_categories', [
        'budget_id' => $budget->id,
        'category_id' => $old->id,
    ])->assertDatabaseHas('budget_categories', [
        'budget_id' => $budget->id,
        'category_id' => $new->id,
        'amount' => '700.00',
    ]);
});

it('does not flash a toast when creating a budget through autosave', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withHeader('X-Budget-Autosave', '1')
        ->post(route('budgets.store'), [
            'month' => '2026-11',
            'amount' => '1000',
        ])
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']))
        ->assertInertiaFlashMissing('toast');

    $this->assertDatabaseHas('budgets', [
        'user_id' => $user->id,
        'year' => 2026,
        'month' => 11,
        'amount' => '1000.00',
    ]);
});

it('does not flash a toast when updating a budget through autosave', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
        'amount' => 1000,
    ]);

    $this->actingAs($user)
        ->withHeader('X-Budget-Autosave', '1')
        ->patch(route('budgets.update', $budget), ['amount' => '1500'])
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']))
        ->assertInertiaFlashMissing('toast');

    expect($budget->refresh()->amount)->toBe('1500.00');
});

it('flashes a toast for manual budget updates', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
        'amount' => 1000,
    ]);

    $this->actingAs($user)
        ->patch(route('budgets.update', $budget), ['amount' => '1500'])
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Presupuesto actualizado.']);

    expect($budget->refresh()->amount)->toBe('1500.00');
});

it('forbids modifying budgets owned by another user', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->patch(route('budgets.update', $budget), ['amount' => 100])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('budgets.destroy', $budget))
        ->assertForbidden();
});

it('deletes a budget and its allocations', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->for($user)->create([
        'year' => 2026,
        'month' => 11,
    ]);
    $category = Category::factory()->for($user)->create();

    BudgetCategory::factory()->for($budget)->for($category, 'category')->create();

    $this->actingAs($user)
        ->delete(route('budgets.destroy', $budget))
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']));

    $this->assertDatabaseMissing('budgets', ['id' => $budget->id])
        ->assertDatabaseMissing('budget_categories', ['budget_id' => $budget->id]);
});
