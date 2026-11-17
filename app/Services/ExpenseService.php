<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ExpenseService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Expense>
     */
    public function listFor(User $user, array $filters): LengthAwarePaginator
    {
        $query = Expense::query()
            ->with('category')
            ->where('user_id', $user->id);

        $month = $filters['month'] ?? null;

        if (is_string($month) && $month !== '') {
            $start = $this->parseMonth($month);

            $query->whereBetween('spent_at', [
                $start->toDateString(),
                $start->endOfMonth()->toDateString(),
            ]);
        }

        $type = $filters['type'] ?? null;

        if (is_string($type) && $type !== '') {
            $query->where('type', $type);
        }

        $categoryId = $filters['category_id'] ?? null;

        if (is_numeric($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        $search = $filters['search'] ?? null;

        if (is_string($search) && trim($search) !== '') {
            $term = '%'.addcslashes(Str::lower(trim($search)), '%_\\').'%';

            $query->where(function (Builder $inner) use ($term): void {
                $inner->whereRaw('lower(description) like ?', [$term])
                    ->orWhereHas('category', fn (Builder $category): Builder => $category->whereRaw('lower(name) like ?', [$term]));
            });
        }

        return $query
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Expense
    {
        return $user->expenses()->create([
            'category_id' => (int) $attributes['category_id'],
            'description' => $attributes['description'],
            'amount' => $attributes['amount'],
            'type' => $attributes['type'],
            'spent_at' => $attributes['spent_at'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Expense $expense, array $attributes): Expense
    {
        $expense->update([
            'category_id' => (int) $attributes['category_id'],
            'description' => $attributes['description'],
            'amount' => $attributes['amount'],
            'type' => $attributes['type'],
            'spent_at' => $attributes['spent_at'],
        ]);

        return $expense;
    }

    public function delete(Expense $expense): bool
    {
        return (bool) $expense->delete();
    }

    private function parseMonth(string $month): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('!Y-m', $month, 'UTC');

        if (! $parsed instanceof CarbonImmutable || $parsed->format('Y-m') !== $month) {
            throw new InvalidArgumentException('Invalid expense month.');
        }

        return $parsed;
    }
}
