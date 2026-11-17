<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BudgetService
{
    /**
     * @return array{month: string, budget: Budget|null, categories: array<int, array<string, mixed>>, summary: array<string, float>}
     */
    public function summaryFor(User $user, string $month): array
    {
        $start = $this->parseMonth($month);
        $end = $start->endOfMonth();

        $budget = Budget::query()
            ->where('user_id', $user->id)
            ->where('year', $start->year)
            ->where('month', $start->month)
            ->first();

        $categories = Category::query()
            ->select(['id', 'name', 'color', 'icon', 'is_default'])
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->withSum(['expenses as month_spent' => function (Builder $query) use ($user, $start, $end): void {
                $query->where('user_id', $user->id)
                    ->whereBetween('spent_at', [$start->toDateString(), $end->toDateString()]);
            }], 'amount');

        if ($budget !== null) {
            $categories->withSum(['budgetCategories as month_limit' => function (Builder $query) use ($budget): void {
                $query->where('budget_id', $budget->id);
            }], 'amount');
        }

        $rows = $categories
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(function (Category $category): array {
                $limit = round((float) $category->getAttribute('month_limit'), 2);
                $spent = round((float) $category->getAttribute('month_spent'), 2);

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'icon' => $category->icon,
                    'is_default' => (bool) $category->is_default,
                    'limit' => $limit,
                    'spent' => $spent,
                    'remaining' => round($limit - $spent, 2),
                    'progress' => $limit > 0 ? min(100.0, round($spent / $limit * 100, 1)) : 0.0,
                ];
            })
            ->all();

        $amount = $budget === null ? 0.0 : round((float) $budget->amount, 2);
        $spentTotal = round((float) array_sum(array_column($rows, 'spent')), 2);
        $allocated = round((float) array_sum(array_column($rows, 'limit')), 2);

        return [
            'month' => $month,
            'budget' => $budget,
            'categories' => $rows,
            'summary' => [
                'amount' => $amount,
                'spent' => $spentTotal,
                'available' => round($amount - $spentTotal, 2),
                'allocated' => $allocated,
                'usage_percent' => $amount > 0 ? min(100.0, round($spentTotal / $amount * 100, 1)) : 0.0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Budget
    {
        $start = $this->parseMonth((string) $attributes['month']);

        return DB::transaction(function () use ($user, $attributes, $start): Budget {
            $budget = $user->budgets()->create([
                'year' => $start->year,
                'month' => $start->month,
                'amount' => $attributes['amount'],
            ]);

            $this->syncAllocations($budget, $attributes['allocations'] ?? []);

            return $budget;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Budget $budget, array $attributes): Budget
    {
        return DB::transaction(function () use ($budget, $attributes): Budget {
            $budget->update(['amount' => $attributes['amount']]);

            $this->syncAllocations($budget, $attributes['allocations'] ?? []);

            return $budget;
        });
    }

    public function delete(Budget $budget): bool
    {
        return (bool) $budget->delete();
    }

    private function syncAllocations(Budget $budget, mixed $allocations): void
    {
        if (! is_array($allocations)) {
            return;
        }

        $sync = [];

        foreach ($allocations as $allocation) {
            if (! is_array($allocation)) {
                continue;
            }

            $categoryId = $allocation['category_id'] ?? null;
            $amount = $allocation['amount'] ?? null;

            if (! is_numeric($categoryId) || ! is_numeric($amount)) {
                continue;
            }

            $sync[(int) $categoryId] = ['amount' => round((float) $amount, 2)];
        }

        $budget->categories()->sync($sync);
    }

    private function parseMonth(string $month): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('!Y-m', $month, 'UTC');

        if ($parsed === null) {
            throw new InvalidArgumentException('Invalid budget month.');
        }

        return $parsed;
    }
}
