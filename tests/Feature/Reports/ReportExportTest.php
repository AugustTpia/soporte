<?php

use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

/**
 * @param  array<string, mixed>  $attributes
 */
function makeExportTicket(CompanyModel $company, array $attributes = []): SupportTicketModel
{
    return SupportTicketModel::factory()->forCompany($company)->create($attributes);
}

test('CSV export is scoped to the active company, sanitizes formulas and is audited', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeExportTicket($company, [
        'subject' => '=SUM(1,1)',
        'status' => TicketStatus::Open->value,
        'priority' => TicketPriority::High->value,
        'opened_at' => now(),
    ]);
    makeExportTicket($otherCompany, [
        'subject' => 'DATO-OTRA-EMPRESA',
        'opened_at' => now(),
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('reports.export', [
        'report' => 'tickets',
        'format' => 'csv',
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect((string) $response->headers->get('cache-control'))
        ->toContain('private')
        ->toContain('no-store');

    $content = $response->streamedContent();

    expect($content)
        ->toContain('Folio')
        ->toContain("'=SUM(1,1)")
        ->not->toContain('DATO-OTRA-EMPRESA');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'report.exported',
        'company_id' => $company->id,
    ]);
});

test('PDF export returns a downloadable PDF document', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeExportTicket($company, [
        'subject' => 'Servidor de archivos',
        'opened_at' => now(),
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('reports.export', [
        'report' => 'tickets',
        'format' => 'pdf',
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect(str_starts_with($response->streamedContent(), '%PDF'))->toBeTrue();
});

test('export rejects an invalid date range', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    $this->get(route('reports.export', [
        'report' => 'tickets',
        'format' => 'csv',
        'from' => now()->addMonth()->toDateString(),
        'to' => now()->subMonth()->toDateString(),
    ]))->assertSessionHasErrors('to');
});

test('cancellation report export is available when the feature is enabled', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    BusTicketCancellationModel::factory()
        ->forCompany($company, $admin)
        ->create([
            'ticket_number' => 'BOL-EXPORT-001',
            'requested_at' => now(),
            'result' => 'cancelled',
            'amount_cents' => 9900,
            'reason' => 'Cancelación solicitada por el cliente',
        ]);

    $this->actingAs($admin);

    $response = $this->get(route('reports.export', [
        'report' => 'cancellations',
        'format' => 'csv',
        'from' => now()->startOfMonth()->toDateString(),
        'to' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertOk();

    expect($response->streamedContent())
        ->toContain('BOL-EXPORT-001')
        ->toContain('Cancelación solicitada por el cliente')
        ->toContain('$99.00');
});
