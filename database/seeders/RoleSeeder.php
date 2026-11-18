<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    private const CATEGORY_PERMISSIONS = [
        'categories.view',
        'categories.create',
        'categories.update',
        'categories.delete',
    ];

    /**
     * Seed the application roles and permissions.
     */
    public function run(): void
    {
        foreach (['admin', 'user'] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        /** @var array<int, Permission> $permissions */
        $permissions = collect(self::CATEGORY_PERMISSIONS)
            ->map(fn (string $name): Permission => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]))
            ->all();

        Role::findByName('admin', 'web')->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
