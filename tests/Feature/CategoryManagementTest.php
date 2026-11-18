<?php

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function categoryManager(): User
{
    $permissions = collect([
        'categories.view',
        'categories.create',
        'categories.update',
        'categories.delete',
    ])
        ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'))
        ->all();

    $user = User::factory()->create();
    $user->syncPermissions($permissions);

    return $user;
}

it('allows only owners to modify their categories', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $own = Category::factory()->for($owner)->create();
    $shared = Category::factory()->default()->create();
    $foreign = Category::factory()->for($other)->create();

    expect(Gate::forUser($owner)->allows('update', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $own))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $shared))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $shared))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('update', $foreign))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $foreign))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('create', Category::class))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('viewAny', Category::class))->toBeTrue();
});

it('loads categories and their expense counts with one categories query', function () {
    $user = User::factory()->create();
    Category::factory()->count(3)->for($user)->create();
    Category::factory()->default()->create();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $categories = app(CategoryService::class)->listFor($user);

    $categoryQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query) => str_contains($query['query'], '"categories"'));

    DB::disableQueryLog();

    expect($categoryQueries)->toHaveCount(1)
        ->and($categories)->toHaveCount(4)
        ->and($categories->every(fn (Category $category) => $category->getAttribute('expenses_count') !== null))->toBeTrue();
});

it('redirects guests to the login page', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
    $this->post(route('categories.store'), [])->assertRedirect(route('login'));
});

it('shows shared and owned categories with their expense counts', function () {
    $user = User::factory()->create();
    $shared = Category::factory()->default()->create(['name' => 'Alimentación']);
    $uncategorized = Category::uncategorized();
    $own = Category::factory()->for($user)->create(['name' => 'Mascotas']);
    Expense::factory()->for($user)->for($own, 'category')->count(2)->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories', 3)
            ->where('categories.0.id', $shared->id)
            ->where('categories.0.expenses_count', 0)
            ->where('categories.1.id', $uncategorized->id)
            ->where('categories.1.expenses_count', 0)
            ->where('categories.2.id', $own->id)
            ->where('categories.2.expenses_count', 2));
});

it('validates the category payload', function () {
    $user = User::factory()->create();
    Category::factory()->default()->create(['name' => 'Transporte']);

    $this->actingAs($user)
        ->post(route('categories.store'), [])
        ->assertSessionHasErrors(['name', 'color']);

    $this->actingAs($user)
        ->post(route('categories.store'), ['name' => 'Mascotas', 'color' => 'red'])
        ->assertSessionHasErrors(['color' => 'El color debe ser un valor hexadecimal como #f97316.']);

    $this->actingAs($user)
        ->post(route('categories.store'), ['name' => 'Transporte', 'color' => '#3b82f6'])
        ->assertSessionHasErrors(['name' => 'Ya existe una categoría con ese nombre.']);
});

it('creates a personal category for the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Mascotas',
            'color' => '#f97316',
            'icon' => 'PawPrint',
        ])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => 'Mascotas',
        'color' => '#f97316',
        'icon' => 'PawPrint',
        'is_default' => false,
    ]);
});

it('updates the authenticated user category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('categories.update', $category), [
            'name' => 'Viajes',
            'color' => '#22c55e',
        ])
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->name)->toBe('Viajes')
        ->and($category->color)->toBe('#22c55e');
});

it('forbids modifying categories owned by another user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->patch(route('categories.update', $category), ['name' => 'Viajes', 'color' => '#22c55e'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();
});

it('forbids deleting shared default categories', function () {
    $user = User::factory()->create();
    $category = Category::factory()->default()->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();
});

it('refuses to delete categories with expenses or budget allocations', function () {
    $user = User::factory()->create();
    $withExpense = Category::factory()->for($user)->create();
    $withBudget = Category::factory()->for($user)->create();
    $budget = Budget::factory()->for($user)->create();

    Expense::factory()->for($user)->for($withExpense, 'category')->create();
    BudgetCategory::factory()->for($budget)->for($withBudget, 'category')->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $withExpense))
        ->assertRedirect(route('categories.index'));

    $this->actingAs($user)
        ->delete(route('categories.destroy', $withBudget))
        ->assertRedirect(route('categories.index'));

    expect(Category::query()->whereKey($withExpense->id)->exists())->toBeTrue()
        ->and(Category::query()->whereKey($withBudget->id)->exists())->toBeTrue();
});

it('deletes an unused personal category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('treats an empty shared flag as a personal category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Mascotas',
            'color' => '#f97316',
            'shared' => '',
        ])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => 'Mascotas',
        'is_default' => false,
    ]);
});

it('validates the shared flag', function () {
    $manager = categoryManager();

    $this->actingAs($manager)
        ->post(route('categories.store'), [
            'name' => 'Bebidas',
            'color' => '#14b8a6',
            'shared' => 'maybe',
        ])
        ->assertSessionHasErrors('shared');
});

it('allows category managers to manage shared categories', function () {
    $manager = categoryManager();
    $shared = Category::factory()->default()->create();
    $uncategorized = Category::uncategorized();

    expect(Gate::forUser($manager)->allows('create', [Category::class, true]))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $shared))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', $shared))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $uncategorized))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('delete', $uncategorized))->toBeFalse();
});

it('creates a shared category when the user has the create permission', function () {
    $manager = categoryManager();

    $this->actingAs($manager)
        ->post(route('categories.store'), [
            'name' => 'Bebidas',
            'color' => '#14b8a6',
            'icon' => 'ShoppingBag',
            'shared' => '1',
        ])
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', [
        'user_id' => null,
        'name' => 'Bebidas',
        'color' => '#14b8a6',
        'icon' => 'ShoppingBag',
        'is_default' => false,
    ]);
});

it('forbids creating shared categories without the create permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Global',
            'color' => '#f97316',
            'shared' => '1',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('categories', ['name' => 'Global']);
});

it('updates shared categories for users with the update permission', function () {
    $manager = categoryManager();
    $shared = Category::factory()->default()->create(['name' => 'Transporte']);

    $this->actingAs($manager)
        ->patch(route('categories.update', $shared), [
            'name' => 'Movilidad',
            'color' => '#22c55e',
            'shared' => '1',
        ])
        ->assertRedirect(route('categories.index'));

    expect($shared->refresh()->name)->toBe('Movilidad')
        ->and($shared->color)->toBe('#22c55e')
        ->and($shared->user_id)->toBeNull();
});

it('forbids updating shared categories without the update permission', function () {
    $user = User::factory()->create();
    $shared = Category::factory()->default()->create(['name' => 'Transporte']);

    $this->actingAs($user)
        ->patch(route('categories.update', $shared), [
            'name' => 'Movilidad',
            'color' => '#22c55e',
        ])
        ->assertForbidden();

    expect($shared->refresh()->name)->toBe('Transporte');
});

it('protects the uncategorized category from privileged edits and deletes', function () {
    $manager = categoryManager();
    $uncategorized = Category::uncategorized();

    $this->actingAs($manager)
        ->patch(route('categories.update', $uncategorized), [
            'name' => 'Otro',
            'color' => '#22c55e',
            'shared' => '1',
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->delete(route('categories.destroy', $uncategorized))
        ->assertForbidden();

    expect($uncategorized->refresh()->name)->toBe(Category::UNCATEGORIZED)
        ->and(Category::query()->whereKey($uncategorized->id)->exists())->toBeTrue();
});

it('converts an owned category to shared for users with the update permission', function () {
    $manager = categoryManager();
    $category = Category::factory()->for($manager)->create();

    $this->actingAs($manager)
        ->patch(route('categories.update', $category), [
            'name' => $category->name,
            'color' => $category->color,
            'shared' => '1',
        ])
        ->assertRedirect(route('categories.index'));

    expect($category->refresh()->user_id)->toBeNull();
});

it('forbids converting another user category to shared', function () {
    $manager = categoryManager();
    $category = Category::factory()->for(User::factory())->create();

    $this->actingAs($manager)
        ->patch(route('categories.update', $category), [
            'name' => $category->name,
            'color' => $category->color,
            'shared' => '1',
        ])
        ->assertForbidden();

    expect($category->refresh()->user_id)->not->toBeNull();
});

it('deletes an unused shared category for users with the delete permission', function () {
    $manager = categoryManager();
    $shared = Category::factory()->default()->create();

    $this->actingAs($manager)
        ->delete(route('categories.destroy', $shared))
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $shared->id]);
});

it('still refuses to delete shared categories in use', function () {
    $manager = categoryManager();
    $shared = Category::factory()->default()->create();
    Expense::factory()->for($manager)->for($shared, 'category')->create();

    $this->actingAs($manager)
        ->delete(route('categories.destroy', $shared))
        ->assertRedirect(route('categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'No se puede eliminar una categoría con gastos o presupuesto asignado.',
        ]);

    expect(Category::query()->whereKey($shared->id)->exists())->toBeTrue();
});

it('shares category permissions with the frontend', function () {
    $this->actingAs(categoryManager())
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.permissions', [
                'categories.view',
                'categories.create',
                'categories.update',
                'categories.delete',
            ]));

    $this->actingAs(User::factory()->create())
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.permissions', []));
});
