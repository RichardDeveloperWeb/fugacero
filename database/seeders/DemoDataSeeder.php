<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo users and their expenses.
     */
    public function run(): void
    {
        $categories = Category::query()->whereNull('user_id')->get();

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => '123456',
        ]);
        $admin->assignRole('admin');

        $users = User::factory()->count(8)->create();

        $users->each(fn (User $user) => $user->assignRole('user'));

        $this->seedExpenses($admin, $categories, 18);

        $users->each(fn (User $user) => $this->seedExpenses($user, $categories, 6));
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function seedExpenses(User $user, Collection $categories, int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            Expense::factory()
                ->for($user)
                ->inCategory($categories->random())
                ->create([
                    'spent_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);
        }
    }
}
