<?php

use App\Assets\Presentation\Livewire\AssetForm;
use App\Assets\Presentation\Livewire\AssetIndex;
use App\Auditing\Presentation\Livewire\AuditIndex;
use App\Companies\Presentation\Livewire\Companies\CompanyForm;
use App\Companies\Presentation\Livewire\Companies\CompanyIndex;
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

    Route::get('users', UserIndex::class)->name('users.index');
    Route::get('users/create', UserForm::class)->name('users.create');
    Route::get('users/{user}/edit', UserForm::class)->name('users.edit');

    Route::get('audit', AuditIndex::class)->name('audit.index');
});

require __DIR__.'/settings.php';
