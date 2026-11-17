<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => ucfirst(fake()->unique()->word()),
            'color' => fake()->randomElement(['#f97316', '#3b82f6', '#22c55e', '#a855f7', '#eab308']),
            'icon' => null,
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the category is a shared default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'is_default' => true,
        ]);
    }
}
