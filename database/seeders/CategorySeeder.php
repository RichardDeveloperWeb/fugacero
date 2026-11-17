<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the shared default categories.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Alimentación', 'color' => '#f97316', 'icon' => 'UtensilsCrossed'],
            ['name' => 'Transporte', 'color' => '#3b82f6', 'icon' => 'CarFront'],
            ['name' => 'Vivienda', 'color' => '#22c55e', 'icon' => 'House'],
            ['name' => 'Servicios', 'color' => '#a855f7', 'icon' => 'Zap'],
            ['name' => 'Entretenimiento', 'color' => '#eab308', 'icon' => 'Clapperboard'],
            ['name' => 'Salud', 'color' => '#ef4444', 'icon' => 'HeartPulse'],
            ['name' => 'Educación', 'color' => '#14b8a6', 'icon' => 'GraduationCap'],
            ['name' => 'Compras', 'color' => '#ec4899', 'icon' => 'ShoppingBag'],
            ['name' => 'Suscripciones', 'color' => '#6366f1', 'icon' => 'Repeat2'],
            ['name' => 'Otros', 'color' => '#64748b', 'icon' => 'Shapes'],
            ['name' => Category::UNCATEGORIZED, 'color' => '#64748b', 'icon' => 'Shapes'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['user_id' => null, 'name' => $category['name']],
                [...$category, 'is_default' => true],
            );
        }
    }
}
