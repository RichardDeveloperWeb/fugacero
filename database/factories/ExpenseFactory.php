<?php

namespace Database\Factories;

use App\ExpenseType;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::uncategorized()->id,
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 5, 500),
            'type' => ExpenseType::Expense,
            'spent_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function inCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Indicate that the expense is a recurring subscription.
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExpenseType::Subscription,
        ]);
    }

    /**
     * Indicate that the expense is a small recurring purchase.
     */
    public function antExpense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ExpenseType::AntExpense,
        ]);
    }
}
