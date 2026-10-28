<?php

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\Identity\Domain\Enums\RoleName;

test('cancellations can be exported to csv with the active filters', function () {
    $company = createTestCompany();
    $foreignCompany = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $foreignAdmin = createTestUser($foreignCompany, [], RoleName::CompanyAdmin);

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-EXPORTADO',
        'requested_at' => '2026-10-10 10:00:00',
        'executed_at' => '2026-10-10 10:30:00',
        'result' => BusTicketCancellationResult::Cancelled->value,
        'reason' => 'Motivo, con coma',
    ]);

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-NO-EXPORTADO',
        'requested_at' => '2026-10-20 10:00:00',
        'executed_at' => '2026-10-20 10:30:00',
        'result' => BusTicketCancellationResult::NotCancelled->value,
    ]);

    makeBusCancellationFor($foreignCompany, $foreignAdmin, [
        'ticket_number' => 'BOL-AJENO',
        'requested_at' => '2026-10-10 10:00:00',
        'executed_at' => '2026-10-10 10:30:00',
        'result' => BusTicketCancellationResult::Cancelled->value,
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('bus-ticket-cancellations.export', [
        'result' => BusTicketCancellationResult::Cancelled->value,
        'from' => '2026-10-01',
        'to' => '2026-10-15',
    ]))->assertOk()->assertDownload();

    $content = $response->streamedContent();

    expect($content)
        ->toContain('Boleto')
        ->toContain('BOL-EXPORTADO')
        ->toContain('Motivo, con coma')
        ->not->toContain('BOL-NO-EXPORTADO')
        ->not->toContain('BOL-AJENO');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'bus_ticket_cancellation.exported',
        'company_id' => $company->id,
    ]);
});

test('csv export prevents spreadsheet formula injection', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => '=SUM(1,1)',
        'requested_at' => '2026-10-10 10:00:00',
        'executed_at' => '2026-10-10 10:30:00',
    ]);

    $this->actingAs($admin);

    $content = $this->get(route('bus-ticket-cancellations.export'))
        ->assertOk()
        ->assertDownload()
        ->streamedContent();

    expect($content)->toContain("'=SUM(1,1)");
});

test('csv export is denied when the bus cancellation feature is disabled', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    $this->get(route('bus-ticket-cancellations.export'))->assertForbidden();
});
