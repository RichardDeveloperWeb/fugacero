<?php

use App\ExpenseType;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(fn () => CarbonImmutable::setTestNow());

it('allows only owners to manage their expenses', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $own = Expense::factory()->for($owner)->create();
    $foreign = Expense::factory()->for($other)->create();

    expect(Gate::forUser($owner)->allows('viewAny', Expense::class))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('create', Expense::class))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $foreign))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('update', $foreign))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $foreign))->toBeFalse();
});

it('filters expenses by month, type, category and search', function () {
    $user = User::factory()->create();
    $own = Category::factory()->for($user)->create(['name' => 'Comida']);
    $shared = Category::factory()->default()->create(['name' => 'Transporte']);
    $foreign = Category::factory()->for(User::factory())->create(['name' => 'Ajenos']);

    Expense::factory()->for($user)->for($own, 'category')->create([
        'description' => 'Café de la mañana',
        'amount' => '150.50',
        'spent_at' => '2026-11-05',
    ]);
    Expense::factory()->for($user)->for($shared, 'category')->create([
        'description' => 'Taxi',
        'amount' => '99.50',
        'spent_at' => '2026-11-20',
    ]);
    Expense::factory()->for($user)->for($own, 'category')->subscription()->create([
        'description' => 'Netflix',
        'amount' => '300',
        'spent_at' => '2026-11-10',
    ]);
    Expense::factory()->for($user)->for($own, 'category')->create([
        'description' => 'Café de octubre',
        'amount' => '800',
        'spent_at' => '2026-10-20',
    ]);
    Expense::factory()->for(User::factory())->for($own, 'category')->create([
        'description' => 'Café ajeno',
        'amount' => '500',
        'spent_at' => '2026-11-20',
    ]);
    Expense::factory()->for($user)->for($foreign, 'category')->create([
        'description' => 'Gasto en categoría ajena',
        'amount' => '400',
        'spent_at' => '2026-11-21',
    ]);

    $service = app(ExpenseService::class);

    expect($service->listFor($user, ['month' => '2026-11'])->total())->toBe(4)
        ->and($service->listFor($user, ['month' => '2026-11', 'type' => 'subscription'])->total())->toBe(1)
        ->and($service->listFor($user, ['month' => '2026-11', 'category_id' => $own->id])->total())->toBe(2)
        ->and($service->listFor($user, ['month' => '2026-11', 'search' => 'café'])->total())->toBe(1)
        ->and($service->listFor($user, ['month' => '2026-11', 'search' => 'Transporte'])->total())->toBe(1)
        ->and($service->listFor($user, [])->total())->toBe(5);
});

it('loads categories eagerly and scopes the list to the user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    Expense::factory()->for($user)->for($category, 'category')->create([
        'spent_at' => '2026-11-05',
    ]);
    Expense::factory()->for(User::factory())->for($category, 'category')->create([
        'spent_at' => '2026-11-06',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $expenses = app(ExpenseService::class)->listFor($user, ['month' => '2026-11']);

    DB::disableQueryLog();

    $queries = collect(DB::getQueryLog())->pluck('query');

    expect($queries->filter(fn (string $sql) => str_contains($sql, 'from "expenses"')))->toHaveCount(2)
        ->and($queries->filter(fn (string $sql) => str_contains($sql, 'from "categories"')))->toHaveCount(1)
        ->and($expenses->total())->toBe(1)
        ->and($expenses->items()[0]->relationLoaded('category'))->toBeTrue();
});

it('redirects guests to the login page', function () {
    $this->get(route('expenses.index'))->assertRedirect(route('login'));
    $this->post(route('expenses.store'), [])->assertRedirect(route('login'));
});

it('defaults the month filter to the current month', function () {
    CarbonImmutable::setTestNow('2026-11-15 12:00:00');

    $user = User::factory()->create();
    Expense::factory()->for($user)->create([
        'description' => 'Gasto del mes actual',
        'spent_at' => '2026-11-05',
    ]);
    Expense::factory()->for($user)->create([
        'description' => 'Gasto del mes anterior',
        'spent_at' => '2026-10-05',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/Index')
            ->where('filters.month', '2026-11')
            ->has('expenses.data', 1)
            ->where('expenses.data.0.description', 'Gasto del mes actual')
            ->where('expenses.total', 1));

    $this->actingAs($user)
        ->get(route('expenses.index', ['month' => '']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.month', '2026-11'));
});

it('exposes the expense list and filters to the expenses page', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Expense::factory()->for($user)->for($category, 'category')->create([
        'description' => 'Café',
        'spent_at' => '2026-11-10',
    ]);
    Expense::factory()->for($user)->create(['spent_at' => '2026-10-10']);

    $this->actingAs($user)
        ->get(route('expenses.index', ['month' => '2026-11', 'type' => 'expense']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/Index')
            ->where('filters.month', '2026-11')
            ->where('filters.type', 'expense')
            ->where('filters.category_id', null)
            ->where('filters.search', null)
            ->has('expenses.data', 1)
            ->where('expenses.data.0.description', 'Café')
            ->where('expenses.total', 1)
            ->has('categories', 2));
});

it('validates the expense payload', function () {
    $user = User::factory()->create();
    $foreign = Category::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [])
        ->assertSessionHasErrors([
            'description',
            'amount',
            'type',
            'category_id' => 'La categoría es obligatoria.',
            'spent_at',
        ]);

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'description' => 'Café',
            'amount' => '10',
            'type' => 'invalid',
            'spent_at' => '2026-11-05',
        ])
        ->assertSessionHasErrors(['type' => 'El tipo seleccionado no es válido.']);

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'description' => 'Café',
            'amount' => '10',
            'type' => 'expense',
            'category_id' => $foreign->id,
            'spent_at' => '2026-11-05',
        ])
        ->assertSessionHasErrors(['category_id' => 'La categoría seleccionada no es válida.']);

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'description' => 'Café',
            'amount' => '10',
            'type' => 'expense',
            'spent_at' => '10/11/2026',
        ])
        ->assertSessionHasErrors(['spent_at' => 'La fecha debe tener el formato AAAA-MM-DD.']);
});

it('creates an expense with a shared category and preserves filters', function () {
    $user = User::factory()->create();
    $category = Category::factory()->default()->create();

    $this->actingAs($user)
        ->post(route('expenses.store', ['month' => '2026-11']), [
            'description' => 'Suscripción de música',
            'amount' => '129.90',
            'type' => 'subscription',
            'category_id' => $category->id,
            'spent_at' => '2026-11-05',
        ])
        ->assertRedirect(route('expenses.index', ['month' => '2026-11']));

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'description' => 'Suscripción de música',
        'amount' => '129.90',
        'type' => 'subscription',
        'spent_at' => '2026-11-05',
    ]);
});

it('updates an expense and preserves filters', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($user)->create([
        'description' => 'Taxi',
        'amount' => '100',
        'type' => 'expense',
        'spent_at' => '2026-11-02',
    ]);

    $this->actingAs($user)
        ->patch(route('expenses.update', ['expense' => $expense, 'month' => '2026-11']), [
            'description' => 'Uber',
            'amount' => '150.25',
            'type' => ExpenseType::AntExpense->value,
            'category_id' => $category->id,
            'spent_at' => '2026-11-03',
        ])
        ->assertRedirect(route('expenses.index', ['month' => '2026-11']));

    expect($expense->refresh()->description)->toBe('Uber')
        ->and($expense->amount)->toBe('150.25')
        ->and($expense->type)->toBe(ExpenseType::AntExpense)
        ->and($expense->category_id)->toBe($category->id)
        ->and($expense->spent_at->toDateString())->toBe('2026-11-03');
});

it('forbids modifying expenses owned by another user', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->patch(route('expenses.update', $expense), [
            'description' => 'Modificado',
            'amount' => '10',
            'type' => 'expense',
            'spent_at' => '2026-11-05',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('expenses.destroy', $expense))
        ->assertForbidden();
});

it('deletes an expense and preserves filters', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->for($user)->create(['spent_at' => '2026-11-05']);

    $this->actingAs($user)
        ->delete(route('expenses.destroy', ['expense' => $expense, 'month' => '2026-11']))
        ->assertRedirect(route('expenses.index', ['month' => '2026-11']));

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});
