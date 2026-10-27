<?php

namespace Database\Seeders;

use App\Identity\Domain\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'companies.view',
            'companies.manage',
            'users.view',
            'users.create',
            'users.update',
            'users.toggle',
            'departments.view',
            'departments.manage',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            RoleName::SuperAdmin->value => $permissions,
            RoleName::CompanyAdmin->value => [
                'companies.view',
                'users.view',
                'users.create',
                'users.update',
                'users.toggle',
                'departments.view',
                'departments.manage',
                'audit.view',
            ],
            RoleName::ServiceManager->value => [
                'users.view',
                'departments.view',
                'departments.manage',
                'audit.view',
            ],
            RoleName::Engineer->value => [],
            RoleName::Technician->value => [],
            RoleName::Auditor->value => [
                'users.view',
                'departments.view',
                'audit.view',
            ],
            RoleName::ReadOnly->value => [
                'users.view',
                'departments.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
