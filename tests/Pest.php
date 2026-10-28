<?php

use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function seedRoles(): void
{
    test()->seed(RolesAndPermissionsSeeder::class);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createTestCompany(array $attributes = []): CompanyModel
{
    return CompanyModel::factory()->create($attributes);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createTestUser(CompanyModel $company, array $attributes = [], ?RoleName $role = null): User
{
    if ($role !== null) {
        seedRoles();
    }

    $user = User::factory()->forCompany($company)->create($attributes);

    if ($role !== null) {
        $user->assignRole($role->value);
    }

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createTestSuperAdmin(array $attributes = []): User
{
    seedRoles();

    $user = User::factory()->create([
        'company_id' => null,
        ...$attributes,
    ]);

    $user->assignRole(RoleName::SuperAdmin->value);

    return $user;
}

function enableBusTicketFeature(CompanyModel $company): void
{
    $settings = $company->settings ?? [];
    $settings['features']['bus_ticket_cancellations'] = true;
    $company->update(['settings' => $settings]);
    $company->refresh();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makeBusCancellationFor(CompanyModel $company, User $operator, array $attributes = []): BusTicketCancellationModel
{
    return BusTicketCancellationModel::factory()
        ->forCompany($company, $operator)
        ->create($attributes);
}
