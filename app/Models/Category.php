<?php

namespace App\Models;

use App\Policies\CategoryPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $color
 * @property string|null $icon
 * @property bool $is_default
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, Expense> $expenses
 * @property-read Collection<int, BudgetCategory> $budgetCategories
 */
#[Fillable(['user_id', 'name', 'color', 'icon', 'is_default'])]
#[UsePolicy(CategoryPolicy::class)]
class Category extends Model
{
    public const string UNCATEGORIZED = 'Sin categoría';

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public static function uncategorized(): self
    {
        return static::firstOrCreate(
            [
                'user_id' => null,
                'name' => self::UNCATEGORIZED,
            ],
            [
                'color' => '#64748b',
                'icon' => 'Shapes',
                'is_default' => true,
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<BudgetCategory, $this>
     */
    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }
}
