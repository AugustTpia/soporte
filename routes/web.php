<?php

use App\Accounts\Presentation\Livewire\ServiceAccountForm;
use App\Accounts\Presentation\Livewire\ServiceAccountIndex;
use App\Assets\Presentation\Livewire\AssetForm;
use App\Assets\Presentation\Livewire\AssetIndex;
use App\Auditing\Presentation\Livewire\AuditIndex;
use App\Companies\Presentation\Livewire\Companies\CompanyForm;
use App\Companies\Presentation\Livewire\Companies\CompanyIndex;
use App\Credentials\Presentation\Http\Controllers\CredentialRevealController;
use App\Credentials\Presentation\Http\Controllers\CredentialSecretController;
use App\Credentials\Presentation\Livewire\CredentialForm;
use App\Credentials\Presentation\Livewire\CredentialIndex;
use App\Departments\Presentation\Livewire\DepartmentForm;
use App\Departments\Presentation\Livewire\DepartmentIndex;
use App\Identity\Presentation\Livewire\Users\UserForm;
use App\Identity\Presentation\Livewire\Users\UserIndex;
use App\Locations\Presentation\Livewire\LocationForm;
use App\Locations\Presentation\Livewire\LocationIndex;
use App\Presentation\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('companies', CompanyIndex::class)->name('companies.index');
    Route::get('companies/create', CompanyForm::class)->name('companies.create');
    Route::get('companies/{company:public_id}/edit', CompanyForm::class)->name('companies.edit');

    Route::get('departments', DepartmentIndex::class)->name('departments.index');
    Route::get('departments/create', DepartmentForm::class)->name('departments.create');
    Route::get('departments/{department}/edit', DepartmentForm::class)->name('departments.edit');

    Route::get('locations', LocationIndex::class)->name('locations.index');
    Route::get('locations/create', LocationForm::class)->name('locations.create');
    Route::get('locations/{location}/edit', LocationForm::class)->name('locations.edit');

    Route::get('assets', AssetIndex::class)->name('assets.index');
    Route::get('assets/create', AssetForm::class)->name('assets.create');
    Route::get('assets/{asset}/edit', AssetForm::class)->name('assets.edit');

    Route::get('accounts', ServiceAccountIndex::class)->name('accounts.index');
    Route::get('accounts/create', ServiceAccountForm::class)->name('accounts.create');
    Route::get('accounts/{account}/edit', ServiceAccountForm::class)->name('accounts.edit');

    Route::get('credentials', CredentialIndex::class)->name('credentials.index');
    Route::get('credentials/create', CredentialForm::class)->name('credentials.create');
    Route::get('credentials/{credential}/edit', CredentialForm::class)->name('credentials.edit');
    Route::post('credentials/{credential}/secret', [CredentialSecretController::class, 'store'])
        ->middleware('throttle:credentials')
        ->name('credentials.secret.store');
    Route::post('credentials/{credential}/reveal', [CredentialRevealController::class, 'store'])
        ->middleware('throttle:credentials')
        ->name('credentials.reveal');

    Route::get('users', UserIndex::class)->name('users.index');
    Route::get('users/create', UserForm::class)->name('users.create');
    Route::get('users/{user}/edit', UserForm::class)->name('users.edit');

    Route::get('audit', AuditIndex::class)->name('audit.index');
});

require __DIR__.'/settings.php';
