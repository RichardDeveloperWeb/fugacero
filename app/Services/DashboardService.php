<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DashboardService
{
    public function __construct(private readonly BudgetService $budgets) {}

    /**
     * @return array{month: string, summary: array<string, float>, top_category: array{id: int, name: string, color: string, icon: string|null, total: float}|null}
     */
    public function summaryFor(User $user, ?string $month = null): array
    {
        $month ??= now()->format('Y-m');
        $summary = $this->budgets->summaryFor($user, $month);

        /** @var array<int, array<string, mixed>> $categories */
        $categories = $summary['categories'];

        $top = collect($categories)
            ->filter(fn (array $category): bool => (float) $category['spent'] > 0)
            ->sortByDesc('spent')
            ->first();

        return [
            'month' => $month,
            'summary' => $summary['summary'],
            'top_category' => $top === null ? null : [
                'id' => (int) $top['id'],
                'name' => (string) $top['name'],
                'color' => (string) $top['color'],
                'icon' => is_string($top['icon']) ? $top['icon'] : null,
                'total' => round((float) $top['spent'], 2),
            ],
        ];
    }

    /**
     * @return array{monthly_spending: array{labels: array<int, string>, spent: array<int, float>, budget: array<int, float>}, category_breakdown: array{categories: array<int, array<string, mixed>>, other_total: float}, monthly_pace: array{labels: array<int, string>, cumulative: array<int, float>, ideal: array<int, float>, budget: float, days_elapsed: int}}
     */
    public function chartsFor(User $user, string $month): array
    {
        return [
            'monthly_spending' => $this->monthlySpendingFor($user, $month),
            'category_breakdown' => $this->categoryBreakdownFor($user, $month),
            'monthly_pace' => $this->monthlyPaceFor($user, $month),
        ];
    }

    /**
     * @return array{labels: array<int, string>, spent: array<int, float>, budget: array<int, float>}
     */
    private function monthlySpendingFor(User $user, string $month, int $months = 6): array
    {
        $end = $this->parseMonth($month);
        $start = $end->startOfMonth()->subMonths($months - 1);
        $lastDay = $end->endOfMonth()->toDateString();

        $spending = DB::table('expenses')
            ->where('user_id', $user->id)
            ->whereBetween('spent_at', [$start->toDateString(), $lastDay])
            ->selectRaw("to_char(spent_at, 'YYYY-MM') as month, SUM(amount) as total")
            ->groupByRaw('1')
            ->orderByRaw('1')
            ->get()
            ->keyBy('month');

        $budgets = DB::table('budgets')
            ->where('user_id', $user->id)
            ->whereRaw('(year * 100 + month) between ? and ?', [
                (int) $start->format('Ym'),
                (int) $end->format('Ym'),
            ])
            ->selectRaw("to_char(make_date(year, month, 1), 'YYYY-MM') as month, SUM(amount) as total")
            ->groupByRaw('1')
            ->orderByRaw('1')
            ->get()
            ->keyBy('month');

        $labels = [];
        $spent = [];
        $budget = [];

        for ($index = 0; $index < $months; $index++) {
            $key = $start->copy()->addMonths($index)->format('Y-m');

            $labels[] = $key;
            $spent[] = round((float) data_get($spending->get($key), 'total', 0), 2);
            $budget[] = round((float) data_get($budgets->get($key), 'total', 0), 2);
        }

        return [
            'labels' => $labels,
            'spent' => $spent,
            'budget' => $budget,
        ];
    }

    /**
     * @return array{categories: array<int, array<string, mixed>>, other_total: float}
     */
    private function categoryBreakdownFor(User $user, string $month, int $limit = 5): array
    {
        $start = $this->parseMonth($month);
        $end = $start->endOfMonth();

        $rows = DB::table('expenses')
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->where('expenses.user_id', $user->id)
            ->whereBetween('expenses.spent_at', [$start->toDateString(), $end->toDateString()])
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
            ->orderBy('categories.id')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'color' => (string) $row->color,
                'icon' => $row->icon === null ? null : (string) $row->icon,
                'total' => round((float) $row->total, 2),
                'expenses_count' => (int) $row->expenses_count,
            ]);

        return [
            'categories' => $rows->take($limit)->values()->all(),
            'other_total' => round((float) $rows->slice($limit)->sum('total'), 2),
        ];
    }

    /**
     * @return array{labels: array<int, string>, cumulative: array<int, float>, ideal: array<int, float>, budget: float, days_elapsed: int}
     */
    private function monthlyPaceFor(User $user, string $month): array
    {
        $start = $this->parseMonth($month);
        $end = $start->endOfMonth();
        $daysInMonth = $start->daysInMonth;

        $budget = (float) DB::table('budgets')
            ->where('user_id', $user->id)
            ->where('year', $start->year)
            ->where('month', $start->month)
            ->value('amount');

        $spending = DB::table('expenses')
            ->where('user_id', $user->id)
            ->whereBetween('spent_at', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('EXTRACT(DAY FROM spent_at) as day, SUM(amount) as total')
            ->groupByRaw('1')
            ->orderByRaw('1')
            ->get()
            ->keyBy('day');

        $labels = [];
        $cumulative = [];
        $ideal = [];
        $runningTotal = 0.0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $runningTotal += (float) data_get($spending->get($day), 'total', 0);

            $labels[] = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
            $cumulative[] = round($runningTotal, 2);
            $ideal[] = round($budget * $day / $daysInMonth, 2);
        }

        return [
            'labels' => $labels,
            'cumulative' => $cumulative,
            'ideal' => $ideal,
            'budget' => round($budget, 2),
            'days_elapsed' => $start->isCurrentMonth() ? now()->day : $daysInMonth,
        ];
    }

    private function parseMonth(string $month): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('!Y-m', $month, 'UTC');

        if ($parsed === null) {
            throw new InvalidArgumentException('Invalid dashboard month.');
        }

        return $parsed;
    }
}
