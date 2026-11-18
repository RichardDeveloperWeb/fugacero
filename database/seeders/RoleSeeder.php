<?php

namespace Database\Seeders;

use App\Models\User;
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
     * Seed the application roles, permissions and user assignments.
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

        $adminRole = Role::findByName('admin', 'web');
        $userRole = Role::findByName('user', 'web');

        $adminRole->syncPermissions($permissions);
        $userRole->syncPermissions([]);

        User::query()
            ->where('email', 'admin@gmail.com')
            ->get()
            ->each(fn (User $user) => $user->assignRole($adminRole));

        User::query()
            ->where('email', '!=', 'admin@gmail.com')
            ->get()
            ->each(fn (User $user) => $user->assignRole($userRole));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
