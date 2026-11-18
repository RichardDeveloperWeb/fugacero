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

    /**
     * @return array{labels: array<int, string>, total_spent: array<int, float>}
     */
    public function monthlySpendingFor(int $months = 12): array
    {
        $end = now()->startOfMonth()->toImmutable();
        $start = $end->subMonths($months - 1);

        $rows = DB::table('expenses')
            ->whereBetween('spent_at', [
                $start->toDateString(),
                $end->endOfMonth()->toDateString(),
            ])
            ->selectRaw("to_char(spent_at, 'YYYY-MM') as month, SUM(amount) as total")
            ->groupByRaw('1')
            ->orderByRaw('1')
            ->get()
            ->keyBy('month');

        $labels = [];
        $totalSpent = [];

        for ($index = 0; $index < $months; $index++) {
            $key = $start->addMonths($index)->format('Y-m');

            $labels[] = $key;
            $totalSpent[] = round((float) data_get($rows->get($key), 'total', 0), 2);
        }

        return [
            'labels' => $labels,
            'total_spent' => $totalSpent,
        ];
    }
}
