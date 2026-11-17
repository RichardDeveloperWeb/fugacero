<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * @return array{users: int, users_with_expenses: int, total_spent: float, average_per_user: float, top_categories: array<int, array<string, mixed>>}
     */
    public function summary(): array
    {
        $users = User::query()->count();
        $usersWithExpenses = Expense::query()->distinct()->count('user_id');
        $totalSpent = round((float) Expense::query()->sum('amount'), 2);

        $topCategories = DB::table('expenses')
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->select([
                'categories.id',
                'categories.name',
                'categories.color',
                'categories.icon',
                DB::raw('SUM(expenses.amount) as total'),
                DB::raw('COUNT(*) as expenses_count'),
            ])
            ->groupBy([
                'categories.id',
                'categories.name',
                'categories.color',
                'categories.icon',
            ])
            ->orderByDesc('total')
            ->orderBy('categories.name')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'color' => (string) $row->color,
                'icon' => $row->icon === null ? null : (string) $row->icon,
                'total' => round((float) $row->total, 2),
                'expenses_count' => (int) $row->expenses_count,
            ])
            ->all();

        return [
            'users' => $users,
            'users_with_expenses' => $usersWithExpenses,
            'total_spent' => $totalSpent,
            'average_per_user' => $users > 0 ? round($totalSpent / $users, 2) : 0.0,
            'top_categories' => $topCategories,
        ];
    }
}
