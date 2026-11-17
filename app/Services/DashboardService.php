<?php

namespace App\Services;

use App\Models\User;

class DashboardService
{
    public function __construct(private readonly BudgetService $budgets) {}

    /**
     * @return array{month: string, summary: array<string, float>, top_category: array{id: int, name: string, color: string, icon: string|null, total: float}|null}
     */
    public function summaryFor(User $user): array
    {
        $month = now()->format('Y-m');
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
}
