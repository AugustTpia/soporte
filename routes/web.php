<?php

use App\Accounts\Presentation\Livewire\ServiceAccountForm;
use App\Accounts\Presentation\Livewire\ServiceAccountIndex;
use App\Assets\Presentation\Livewire\AssetForm;
use App\Assets\Presentation\Livewire\AssetIndex;
use App\Auditing\Presentation\Livewire\AuditIndex;
use App\BusTicketCancellations\Presentation\Http\Controllers\BusTicketCancellationAttachmentDownloadController;
use App\BusTicketCancellations\Presentation\Http\Controllers\BusTicketCancellationExportController;
use App\BusTicketCancellations\Presentation\Livewire\BusTicketCancellationForm;
use App\BusTicketCancellations\Presentation\Livewire\BusTicketCancellationIndex;
use App\BusTicketCancellations\Presentation\Livewire\BusTicketCancellationShow;
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
use App\Maintenance\Presentation\Http\Controllers\MaintenanceExecutionAttachmentDownloadController;
use App\Maintenance\Presentation\Livewire\MaintenanceExecutionForm;
use App\Maintenance\Presentation\Livewire\MaintenanceExecutionIndex;
use App\Maintenance\Presentation\Livewire\MaintenanceExecutionShow;
use App\Maintenance\Presentation\Livewire\MaintenancePlanForm;
use App\Maintenance\Presentation\Livewire\MaintenancePlanIndex;
use App\Maintenance\Presentation\Livewire\MaintenancePlanShow;
use App\Presentation\Livewire\Dashboard;
use App\Reports\Presentation\Http\Controllers\ReportExportController;
use App\Reports\Presentation\Livewire\ReportIndex;
use App\Support\Presentation\Http\Controllers\TicketAttachmentDownloadController;
use App\Support\Presentation\Livewire\Tickets\TicketForm;
use App\Support\Presentation\Livewire\Tickets\TicketIndex;
use App\Support\Presentation\Livewire\Tickets\TicketShow;
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

    Route::get('tickets', TicketIndex::class)->name('tickets.index');
    Route::get('tickets/create', TicketForm::class)->name('tickets.create');
    Route::get('tickets/{ticket}', TicketShow::class)->name('tickets.show');
    Route::get('tickets/{ticket}/edit', TicketForm::class)->name('tickets.edit');
    Route::get('tickets/{ticket}/attachments/{attachment}/download', TicketAttachmentDownloadController::class)
        ->whereNumber('attachment')
        ->name('tickets.attachments.download');

    Route::get('maintenance', MaintenancePlanIndex::class)->name('maintenance.index');
    Route::get('maintenance/plans/create', MaintenancePlanForm::class)->name('maintenance.plans.create');
    Route::get('maintenance/plans/{plan}', MaintenancePlanShow::class)->name('maintenance.plans.show');
    Route::get('maintenance/plans/{plan}/edit', MaintenancePlanForm::class)->name('maintenance.plans.edit');

    Route::get('maintenance/executions', MaintenanceExecutionIndex::class)->name('maintenance.executions.index');
    Route::get('maintenance/executions/create/{plan?}', MaintenanceExecutionForm::class)
        ->whereNumber('plan')
        ->name('maintenance.executions.create');
    Route::get('maintenance/executions/{execution}/edit', MaintenanceExecutionForm::class)->name('maintenance.executions.edit');
    Route::get('maintenance/executions/{execution}', MaintenanceExecutionShow::class)->name('maintenance.executions.show');
    Route::get('maintenance/executions/{execution}/attachments/{attachment}/download', MaintenanceExecutionAttachmentDownloadController::class)
        ->whereNumber('execution')
        ->whereNumber('attachment')
        ->name('maintenance.executions.attachments.download');

    Route::middleware('company.feature:bus_ticket_cancellations')->group(function () {
        Route::get('bus-ticket-cancellations', BusTicketCancellationIndex::class)->name('bus-ticket-cancellations.index');
        Route::get('bus-ticket-cancellations/create', BusTicketCancellationForm::class)->name('bus-ticket-cancellations.create');
        Route::get('bus-ticket-cancellations/export', BusTicketCancellationExportController::class)->name('bus-ticket-cancellations.export');
        Route::get('bus-ticket-cancellations/{cancellation}', BusTicketCancellationShow::class)->name('bus-ticket-cancellations.show');
        Route::get('bus-ticket-cancellations/{cancellation}/edit', BusTicketCancellationForm::class)->name('bus-ticket-cancellations.edit');
        Route::get('bus-ticket-cancellations/{cancellation}/attachments/{attachment}/download', BusTicketCancellationAttachmentDownloadController::class)
            ->whereNumber('attachment')
            ->name('bus-ticket-cancellations.attachments.download');
    });

    Route::get('reports', ReportIndex::class)->name('reports.index');
    Route::get('reports/export/{report}', ReportExportController::class)->name('reports.export');

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
