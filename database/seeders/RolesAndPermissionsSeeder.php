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
            'contacts.view',
            'contacts.manage',
            'users.view',
            'users.create',
            'users.update',
            'users.toggle',
            'departments.view',
            'departments.manage',
            'locations.view',
            'locations.manage',
            'assets.view',
            'assets.manage',
            'accounts.view',
            'accounts.manage',
            'credentials.view',
            'credentials.manage',
            'credentials.reveal',
            'tickets.view',
            'tickets.create',
            'tickets.manage',
            'tickets.assign',
            'tickets.cancel',
            'bus_ticket_cancellations.view',
            'bus_ticket_cancellations.manage',
            'maintenance.view',
            'maintenance.manage',
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
                'contacts.view',
                'contacts.manage',
                'users.view',
                'users.create',
                'users.update',
                'users.toggle',
                'departments.view',
                'departments.manage',
                'locations.view',
                'locations.manage',
                'assets.view',
                'assets.manage',
                'accounts.view',
                'accounts.manage',
                'credentials.view',
                'credentials.manage',
                'credentials.reveal',
                'tickets.view',
                'tickets.create',
                'tickets.manage',
                'tickets.assign',
                'tickets.cancel',
                'bus_ticket_cancellations.view',
                'bus_ticket_cancellations.manage',
                'maintenance.view',
                'maintenance.manage',
                'audit.view',
            ],
            RoleName::ServiceManager->value => [
                'companies.view',
                'contacts.view',
                'contacts.manage',
                'users.view',
                'departments.view',
                'departments.manage',
                'locations.view',
                'assets.view',
                'assets.manage',
                'accounts.view',
                'accounts.manage',
                'credentials.view',
                'credentials.manage',
                'credentials.reveal',
                'tickets.view',
                'tickets.create',
                'tickets.manage',
                'tickets.assign',
                'tickets.cancel',
                'bus_ticket_cancellations.view',
                'bus_ticket_cancellations.manage',
                'maintenance.view',
                'maintenance.manage',
                'audit.view',
            ],
            RoleName::Engineer->value => [
                'companies.view',
                'contacts.view',
                'locations.view',
                'assets.view',
                'assets.manage',
                'accounts.view',
                'accounts.manage',
                'credentials.view',
                'tickets.view',
                'tickets.create',
                'tickets.manage',
                'bus_ticket_cancellations.view',
                'maintenance.view',
                'maintenance.manage',
            ],
            RoleName::Technician->value => [
                'companies.view',
                'contacts.view',
                'locations.view',
                'assets.view',
                'assets.manage',
                'accounts.view',
                'accounts.manage',
                'credentials.view',
                'tickets.view',
                'tickets.create',
                'tickets.manage',
                'bus_ticket_cancellations.view',
                'maintenance.view',
                'maintenance.manage',
            ],
            RoleName::Auditor->value => [
                'companies.view',
                'contacts.view',
                'users.view',
                'departments.view',
                'locations.view',
                'assets.view',
                'accounts.view',
                'credentials.view',
                'tickets.view',
                'bus_ticket_cancellations.view',
                'maintenance.view',
                'audit.view',
            ],
            RoleName::ReadOnly->value => [
                'companies.view',
                'contacts.view',
                'users.view',
                'departments.view',
                'locations.view',
                'assets.view',
                'accounts.view',
                'credentials.view',
                'tickets.view',
                'bus_ticket_cancellations.view',
                'maintenance.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
