<?php

use App\ExpenseType;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds shared default categories only once', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::query()->whereNull('user_id')->count())->toBe(11)
        ->and(Category::query()->whereNull('user_id')->where('is_default', true)->count())->toBe(11);
});

it('casts expense attributes to money, enum and date values', function () {
    $expense = Expense::factory()->create([
        'amount' => '123.45',
        'spent_at' => '2026-11-01',
    ]);

    expect($expense->amount)->toBe('123.45')
        ->and($expense->type)->toBe(ExpenseType::Expense)
        ->and($expense->spent_at->toDateString())->toBe('2026-11-01');

    $subscription = Expense::factory()->subscription()->create();
    $antExpense = Expense::factory()->antExpense()->create();

    expect($subscription->type)->toBe(ExpenseType::Subscription)
        ->and($antExpense->type)->toBe(ExpenseType::AntExpense);
});

it('relates finance records to their owner', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($user)->for($category, 'category')->create();
    $budget = Budget::factory()->for($user)->create();
    $budgetCategory = BudgetCategory::factory()->for($budget)->for($category, 'category')->create();

    expect($user->categories)->toHaveCount(1)
        ->and($user->expenses)->toHaveCount(1)
        ->and($user->budgets)->toHaveCount(1)
        ->and($budget->categories)->toHaveCount(1)
        ->and($budget->budgetCategories)->toHaveCount(1)
        ->and($budgetCategory->category->is($category))->toBeTrue();
});

it('removes owned finance records when the user is deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $expense = Expense::factory()->for($user)->for($category, 'category')->create();
    $budget = Budget::factory()->for($user)->create();
    $budgetCategory = BudgetCategory::factory()->for($budget)->for($category, 'category')->create();

    $user->delete();

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse()
        ->and(Expense::query()->whereKey($expense->id)->exists())->toBeFalse()
        ->and(Budget::query()->whereKey($budget->id)->exists())->toBeFalse()
        ->and(BudgetCategory::query()->whereKey($budgetCategory->id)->exists())->toBeFalse();
});
