<?php

use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Reports\Presentation\Livewire\ReportIndex;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $attributes
 */
function makeReportTicket(CompanyModel $company, array $attributes = []): SupportTicketModel
{
    return SupportTicketModel::factory()->forCompany($company)->create($attributes);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makeReportExecution(CompanyModel $company, User $actor, array $attributes = []): MaintenanceExecutionModel
{
    return MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => 'Servicio reportado',
        'type' => 'preventive',
        'status' => 'scheduled',
        'scheduled_at' => now(),
        'created_by' => $actor->id,
        ...$attributes,
    ]);
}

test('company users can open operational reports scoped to their company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeReportTicket($company, [
        'subject' => 'Servidor principal sin respuesta',
        'status' => TicketStatus::Open->value,
        'priority' => TicketPriority::High->value,
        'opened_at' => now(),
    ]);
    makeReportTicket($otherCompany, [
        'subject' => 'Secreto de otra empresa',
        'opened_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ReportIndex::class)
        ->assertOk()
        ->assertSee('Reportes operativos')
        ->assertSee('Servidor principal sin respuesta')
        ->assertSee('Total de tickets')
        ->assertDontSee('Secreto de otra empresa')
        ->assertDontSee('Cancelaciones de boletos');
});

test('ticket report filters by status and search term', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeReportTicket($company, [
        'subject' => 'Ticket resuelto único',
        'status' => TicketStatus::Resolved->value,
        'opened_at' => now()->subHours(3),
        'resolved_at' => now()->subHour(),
    ]);
    makeReportTicket($company, [
        'subject' => 'Ticket urgente pendiente',
        'status' => TicketStatus::Open->value,
        'priority' => TicketPriority::Urgent->value,
        'opened_at' => now(),
    ]);
    makeReportTicket($company, [
        'subject' => 'Ticket anterior',
        'status' => TicketStatus::Open->value,
        'opened_at' => now()->subMonths(2),
    ]);

    $this->actingAs($admin);

    Livewire::test(ReportIndex::class)
        ->set('status', TicketStatus::Resolved->value)
        ->assertSee('Ticket resuelto único')
        ->assertDontSee('Ticket urgente pendiente')
        ->assertDontSee('Ticket anterior')
        ->set('status', '')
        ->set('search', 'urgente pendiente')
        ->assertSee('Ticket urgente pendiente')
        ->assertDontSee('Ticket resuelto único');
});

test('maintenance and cancellation reports expose their own metrics and filters', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeReportExecution($company, $admin, [
        'target_label' => 'Servidor de sucursal norte',
        'status' => 'completed',
        'scheduled_at' => now()->subDay(),
        'completed_at' => now(),
        'time_spent_minutes' => 90,
    ]);

    BusTicketCancellationModel::factory()
        ->forCompany($company, $admin)
        ->create([
            'ticket_number' => 'BOL-REPORTE-001',
            'requested_at' => now()->subHour(),
            'executed_at' => now(),
            'amount_cents' => 12345,
            'result' => 'cancelled',
        ]);

    $this->actingAs($admin);

    Livewire::test(ReportIndex::class)
        ->call('switchReport', 'maintenance')
        ->assertSee('Servidor de sucursal norte')
        ->assertSee('Tiempo medio invertido')
        ->assertSee('1 h 30 min')
        ->call('switchReport', 'cancellations')
        ->assertSee('BOL-REPORTE-001')
        ->assertSee('$123.45')
        ->assertSee('Importe registrado');
});

test('reports page validates the date range', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    Livewire::test(ReportIndex::class)
        ->set('from', now()->addMonth()->toDateString())
        ->set('to', now()->subMonth()->toDateString())
        ->assertHasErrors('to');
});

test('users only see report tabs allowed by their permissions', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    seedRoles();
    $user = createTestUser($company);
    $user->givePermissionTo('maintenance.view');

    $this->actingAs($user);

    Livewire::test(ReportIndex::class)
        ->assertOk()
        ->assertSee('Mantenimientos')
        ->assertDontSee('Tickets')
        ->assertDontSee('Cancelaciones de boletos');

    $this->get(route('reports.export', [
        'report' => 'tickets',
        'format' => 'csv',
    ]))->assertForbidden();
});

test('users without report permissions are denied', function () {
    $company = createTestCompany();
    seedRoles();
    $user = createTestUser($company);

    $this->actingAs($user);

    $this->get(route('reports.index'))->assertForbidden();
    $this->get(route('reports.export', [
        'report' => 'tickets',
        'format' => 'csv',
    ]))->assertForbidden();
});

test('cancellation exports are blocked when the company feature is disabled', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    $this->get(route('reports.export', [
        'report' => 'cancellations',
        'format' => 'csv',
    ]))->assertForbidden();
});

test('super admins without an active company are redirected', function () {
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin);

    $this->get(route('reports.index'))->assertRedirect(route('companies.index'));
});
