<?php

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Application\UseCases\SummarizeBusTicketCancellations;
use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\BusTicketCancellations\Presentation\Livewire\BusTicketCancellationForm;
use App\BusTicketCancellations\Presentation\Livewire\BusTicketCancellationIndex;
use App\Identity\Domain\Enums\RoleName;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use DateTimeImmutable;
use Livewire\Livewire;

test('company administrators can register a bus ticket cancellation', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $supportTicket = SupportTicketModel::factory()->forCompany($company, $admin)->create();

    $this->actingAs($admin);

    $component = Livewire::test(BusTicketCancellationForm::class)
        ->set('ticketNumber', 'BOL-123456')
        ->set('supportTicketId', (string) $supportTicket->id)
        ->set('requestedAt', '2026-10-27T12:00')
        ->set('executedAt', '2026-10-27T12:30')
        ->set('originalTicketAt', '2026-10-28T08:00')
        ->set('amount', '1250.50')
        ->set('operatorId', (string) $admin->id)
        ->set('result', BusTicketCancellationResult::Cancelled->value)
        ->set('reason', 'El pasajero solicitó la cancelación.')
        ->set('authorizationReference', 'AUT-9988')
        ->set('evidenceNotes', 'Comprobante resguardado en recepción.')
        ->call('save')
        ->assertHasNoErrors();

    $cancellation = BusTicketCancellationModel::query()->firstOrFail();

    $component->assertRedirect(route('bus-ticket-cancellations.index'));

    expect($cancellation->company_id)->toBe($company->id)
        ->and($cancellation->support_ticket_id)->toBe($supportTicket->id)
        ->and($cancellation->ticket_number)->toBe('BOL-123456')
        ->and($cancellation->amount_cents)->toBe(125050)
        ->and($cancellation->operator_id)->toBe($admin->id)
        ->and($cancellation->result)->toBe('cancelled')
        ->and($cancellation->created_by)->toBe($admin->id);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'bus_ticket_cancellation.created',
        'company_id' => $company->id,
        'subject_id' => $cancellation->id,
    ]);
});

test('the bus ticket cancellation module is hidden and denied when disabled', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    $this->get(route('bus-ticket-cancellations.index'))->assertForbidden();
    $this->get(route('bus-ticket-cancellations.create'))->assertForbidden();
});

test('the feature flag also applies to super administrators and the sidebar', function () {
    $company = createTestCompany();
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin);
    $this->withSession(['active_company_id' => $company->id]);

    $this->get(route('bus-ticket-cancellations.index'))->assertForbidden();
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Cancelación de boletos');

    enableBusTicketFeature($company);

    $this->get(route('bus-ticket-cancellations.index'))->assertOk();
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cancelación de boletos');
});

test('engineers can view cancellations but can not manage them', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $cancellation = makeBusCancellationFor($company, $engineer);

    $this->actingAs($engineer);

    $this->get(route('bus-ticket-cancellations.index'))->assertOk();
    $this->get(route('bus-ticket-cancellations.show', $cancellation))->assertOk();
    $this->get(route('bus-ticket-cancellations.create'))->assertForbidden();
});

test('executed cancellations require an execution date and valid order', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    Livewire::test(BusTicketCancellationForm::class)
        ->set('ticketNumber', 'BOL-123456')
        ->set('requestedAt', '2026-10-27T12:00')
        ->set('operatorId', (string) $admin->id)
        ->set('result', BusTicketCancellationResult::Cancelled->value)
        ->set('executedAt', '')
        ->set('reason', 'Motivo válido')
        ->call('save')
        ->assertHasErrors('form');

    Livewire::test(BusTicketCancellationForm::class)
        ->set('ticketNumber', 'BOL-123456')
        ->set('requestedAt', '2026-10-27T12:00')
        ->set('operatorId', (string) $admin->id)
        ->set('result', BusTicketCancellationResult::Cancelled->value)
        ->set('executedAt', '2026-10-27T11:00')
        ->set('reason', 'Motivo válido')
        ->call('save')
        ->assertHasErrors('executedAt');

    $this->assertDatabaseCount('bus_ticket_cancellations', 0);
});

test('pending cancellations can be registered without execution date or support ticket', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    Livewire::test(BusTicketCancellationForm::class)
        ->set('ticketNumber', 'BOL-PENDIENTE')
        ->set('requestedAt', '2026-10-27T12:00')
        ->set('operatorId', (string) $admin->id)
        ->set('result', BusTicketCancellationResult::Pending->value)
        ->set('reason', 'Pendiente de autorización del proveedor.')
        ->call('save')
        ->assertHasNoErrors();

    $cancellation = BusTicketCancellationModel::query()->firstOrFail();

    expect($cancellation->executed_at)->toBeNull()
        ->and($cancellation->support_ticket_id)->toBeNull()
        ->and($cancellation->amount_cents)->toBeNull()
        ->and($cancellation->result)->toBe('pending');
});

test('cancellations are isolated by company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    enableBusTicketFeature($company);
    enableBusTicketFeature($otherCompany);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherAdmin = createTestUser($otherCompany, [], RoleName::CompanyAdmin);
    $ownCancellation = makeBusCancellationFor($company, $admin, ['ticket_number' => 'BOL-PROPIO']);
    $foreignCancellation = makeBusCancellationFor($otherCompany, $otherAdmin, ['ticket_number' => 'BOL-AJENO']);

    $this->actingAs($admin);

    $this->get(route('bus-ticket-cancellations.index'))
        ->assertOk()
        ->assertSee('BOL-PROPIO')
        ->assertDontSee('BOL-AJENO');

    $this->get(route('bus-ticket-cancellations.show', $foreignCancellation))
        ->assertNotFound();

    $this->get(route('bus-ticket-cancellations.edit', $foreignCancellation))
        ->assertForbidden();

    $this->get(route('bus-ticket-cancellations.show', $ownCancellation))
        ->assertOk();

    expect($ownCancellation->company_id)->toBe($company->id);
});

test('reports calculate totals by range and support ticket links are optional', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $supportTicket = SupportTicketModel::factory()->forCompany($company, $admin)->create();

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-001',
        'support_ticket_id' => $supportTicket->id,
        'requested_at' => '2026-10-05 10:00:00',
        'executed_at' => '2026-10-05 10:30:00',
        'amount_cents' => 100000,
        'result' => BusTicketCancellationResult::Cancelled->value,
    ]);

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-002',
        'support_ticket_id' => $supportTicket->id,
        'requested_at' => '2026-10-06 10:00:00',
        'executed_at' => '2026-10-06 10:30:00',
        'amount_cents' => 50000,
        'result' => BusTicketCancellationResult::NotCancelled->value,
    ]);

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-003',
        'requested_at' => '2026-10-20 10:00:00',
        'executed_at' => null,
        'amount_cents' => 25000,
        'result' => BusTicketCancellationResult::Pending->value,
    ]);

    $summary = app(SummarizeBusTicketCancellations::class)->execute(
        $company->id,
        new BusTicketCancellationFilters(
            from: new DateTimeImmutable('2026-10-01'),
            to: new DateTimeImmutable('2026-10-15'),
        ),
    );

    expect($summary)->toBe([
        'total' => 2,
        'cancelled' => 1,
        'not_cancelled' => 1,
        'pending' => 0,
        'total_amount_cents' => 150000,
    ]);

    expect(BusTicketCancellationModel::query()
        ->where('support_ticket_id', $supportTicket->id)
        ->count())->toBe(2);
});

test('cancellations can be updated and leave an audit trail', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $cancellation = makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-EDIT',
        'amount_cents' => 10000,
        'requested_at' => '2026-10-27 10:00:00',
        'executed_at' => '2026-10-27 10:30:00',
    ]);

    $this->actingAs($admin);

    Livewire::test(BusTicketCancellationForm::class, ['cancellation' => $cancellation])
        ->set('amount', '250.00')
        ->set('reason', 'Se corrigió el importe registrado.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('bus-ticket-cancellations.index'));

    expect($cancellation->refresh()->amount_cents)->toBe(25000)
        ->and($cancellation->reason)->toBe('Se corrigió el importe registrado.');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'bus_ticket_cancellation.updated',
        'company_id' => $company->id,
        'subject_id' => $cancellation->id,
    ]);
});

test('the index applies result and date filters', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-CANCELLED',
        'requested_at' => '2026-10-10 10:00:00',
        'executed_at' => '2026-10-10 10:30:00',
        'result' => BusTicketCancellationResult::Cancelled->value,
    ]);

    makeBusCancellationFor($company, $admin, [
        'ticket_number' => 'BOL-PENDING',
        'requested_at' => '2026-10-20 10:00:00',
        'executed_at' => null,
        'result' => BusTicketCancellationResult::Pending->value,
    ]);

    $this->actingAs($admin);

    Livewire::test(BusTicketCancellationIndex::class)
        ->set('result', BusTicketCancellationResult::Cancelled->value)
        ->set('from', '2026-10-01')
        ->set('to', '2026-10-15')
        ->assertSee('BOL-CANCELLED')
        ->assertDontSee('BOL-PENDING');
});
