<?php

use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Reports\Application\Contracts\CancellationReportQuery;
use App\Reports\Application\Contracts\MaintenanceReportQuery;
use App\Reports\Application\Contracts\TicketReportQuery;
use App\Reports\Application\DTOs\CancellationReportFilters;
use App\Reports\Application\DTOs\MaintenanceReportFilters;
use App\Reports\Application\DTOs\ReportPeriod;
use App\Reports\Application\DTOs\TicketReportFilters;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

/**
 * @param  array<string, mixed>  $attributes
 */
function makeQueryTicket(CompanyModel $company, array $attributes = []): SupportTicketModel
{
    return SupportTicketModel::factory()->forCompany($company)->create($attributes);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makeQueryExecution(CompanyModel $company, User $actor, array $attributes = []): MaintenanceExecutionModel
{
    return MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => 'Ejecución del periodo',
        'type' => 'preventive',
        'status' => 'completed',
        'scheduled_at' => now(),
        'created_by' => $actor->id,
        ...$attributes,
    ]);
}

test('ticket report summary calculates unresolved, urgent and average resolution time', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeQueryTicket($company, [
        'status' => 'resolved',
        'priority' => 'high',
        'opened_at' => now()->subHours(4),
        'resolved_at' => now()->subHours(2),
    ]);
    makeQueryTicket($company, [
        'status' => 'resolved',
        'priority' => 'medium',
        'opened_at' => now()->subHours(5),
        'resolved_at' => now()->subHour(),
    ]);
    makeQueryTicket($company, [
        'status' => 'open',
        'priority' => 'urgent',
        'opened_at' => now(),
    ]);
    makeQueryTicket($otherCompany, [
        'status' => 'open',
        'priority' => 'urgent',
        'opened_at' => now(),
    ]);

    $summary = app(TicketReportQuery::class)->summary($company->id, new TicketReportFilters(
        period: ReportPeriod::default(),
    ));

    expect($summary['total'])->toBe(3)
        ->and($summary['resolved'])->toBe(2)
        ->and($summary['unresolved'])->toBe(1)
        ->and($summary['urgent'])->toBe(1)
        ->and($summary['avg_resolution_hours'])->toBe(3.0);
});

test('maintenance and cancellation summaries respect company and filters', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherAdmin = createTestUser($otherCompany, [], RoleName::CompanyAdmin);

    makeQueryExecution($company, $admin, ['time_spent_minutes' => 30]);
    makeQueryExecution($company, $admin, ['time_spent_minutes' => 90]);
    makeQueryExecution($otherCompany, $otherAdmin, ['time_spent_minutes' => 300]);

    BusTicketCancellationModel::factory()->forCompany($company, $admin)->create([
        'requested_at' => now(),
        'result' => 'cancelled',
        'amount_cents' => 10000,
    ]);
    BusTicketCancellationModel::factory()->forCompany($company, $admin)->create([
        'requested_at' => now(),
        'result' => 'not_cancelled',
        'amount_cents' => 2500,
    ]);
    BusTicketCancellationModel::factory()->forCompany($otherCompany, $otherAdmin)->create([
        'requested_at' => now(),
        'result' => 'cancelled',
        'amount_cents' => 99999,
    ]);

    $maintenance = app(MaintenanceReportQuery::class)->summary($company->id, new MaintenanceReportFilters(
        period: ReportPeriod::default(),
    ));
    $cancellations = app(CancellationReportQuery::class)->summary($company->id, new CancellationReportFilters(
        period: ReportPeriod::default(),
    ));

    expect($maintenance['total'])->toBe(2)
        ->and($maintenance['completed'])->toBe(2)
        ->and($maintenance['avg_minutes'])->toBe(60.0)
        ->and($cancellations['total'])->toBe(2)
        ->and($cancellations['cancelled'])->toBe(1)
        ->and($cancellations['not_cancelled'])->toBe(1)
        ->and($cancellations['amount_cents'])->toBe(12500);
});
