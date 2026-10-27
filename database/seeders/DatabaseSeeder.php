<?php

namespace Database\Seeders;

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $this->seedSuperAdmin();
    }

    private function seedSuperAdmin(): void
    {
        $email = config('superadmin.email');
        $password = config('superadmin.password');

        if (! is_string($email) || ! is_string($password) || trim($email) === '' || $password === '') {
            return;
        }

        $name = config('superadmin.name', 'Superadministrador');
        $name = is_string($name) && trim($name) !== '' ? trim($name) : 'Superadministrador';

        $user = User::query()->updateOrCreate(
            ['email' => mb_strtolower(trim($email))],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'company_id' => null,
                'status' => 'active',
                'email_verified_at' => now(),
                'deactivated_at' => null,
            ],
        );

        $user->syncRoles([RoleName::SuperAdmin->value]);
    }
}
