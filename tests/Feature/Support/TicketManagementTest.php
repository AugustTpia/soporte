<?php

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Support\Application\UseCases\CountDashboardTickets;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAssignmentModel;
use App\Support\Presentation\Livewire\Tickets\TicketForm;
use App\Support\Presentation\Livewire\Tickets\TicketIndex;
use App\Support\Presentation\Livewire\Tickets\TicketShow;
use Livewire\Livewire;

function makeSupportTicket(CompanyModel $company, ?User $creator = null, array $attributes = []): SupportTicketModel
{
    return SupportTicketModel::factory()
        ->forCompany($company, $creator)
        ->create($attributes);
}

function assignSupportTicket(SupportTicketModel $ticket, User $technician): TicketAssignmentModel
{
    return TicketAssignmentModel::query()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $technician->id,
        'assigned_by' => $ticket->created_by,
        'assigned_at' => now(),
    ]);
}

test('company administrators can create tickets with a generated folio', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($admin);

    $component = Livewire::test(TicketForm::class)
        ->set('category', 'connectivity')
        ->set('subject', 'Sin acceso a internet')
        ->set('description', 'La sucursal no tiene conectividad desde las 9:00.')
        ->set('priority', 'high')
        ->set('requesterName', 'María López')
        ->set('requesterContact', '555-123-4567')
        ->set('locationId', (string) $location->id)
        ->call('save')
        ->assertHasNoErrors();

    $ticket = SupportTicketModel::query()->firstOrFail();

    $component->assertRedirect(route('tickets.show', $ticket->id));

    expect($ticket->folio)->toBe('TCK-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT))
        ->and($ticket->company_id)->toBe($company->id)
        ->and($ticket->status)->toBe(TicketStatus::Open->value)
        ->and($ticket->category)->toBe('connectivity')
        ->and($ticket->priority)->toBe('high')
        ->and($ticket->location_id)->toBe($location->id)
        ->and($ticket->created_by)->toBe($admin->id);

    $this->assertDatabaseHas('ticket_activities', [
        'ticket_id' => $ticket->id,
        'actor_id' => $admin->id,
        'type' => 'created',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'ticket.created',
        'company_id' => $company->id,
        'subject_id' => $ticket->id,
    ]);
});

test('tickets can not link a location from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($admin);

    Livewire::test(TicketForm::class)
        ->set('category', 'hardware')
        ->set('subject', 'Equipo sin encender')
        ->set('description', 'El equipo no enciende.')
        ->set('priority', 'medium')
        ->set('locationId', (string) $otherLocation->id)
        ->call('save')
        ->assertHasErrors('locationId');

    $this->assertDatabaseCount('support_tickets', 0);
});

test('several technicians can be assigned to the same ticket', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $firstTechnician = createTestUser($company, [], RoleName::Technician);
    $secondTechnician = createTestUser($company, [], RoleName::Technician);
    $ticket = makeSupportTicket($company, $admin);

    $this->actingAs($admin);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->set('assignUserId', (string) $firstTechnician->id)
        ->call('assign')
        ->assertHasNoErrors();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Assigned->value);

    Livewire::test(TicketShow::class, ['ticket' => $ticket->refresh()])
        ->set('assignUserId', (string) $secondTechnician->id)
        ->call('assign')
        ->assertHasNoErrors();

    expect(TicketAssignmentModel::query()
        ->where('ticket_id', $ticket->id)
        ->whereNull('unassigned_at')
        ->count())->toBe(2);

    $this->assertDatabaseHas('ticket_activities', [
        'ticket_id' => $ticket->id,
        'type' => 'assigned',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'ticket.assigned',
        'company_id' => $company->id,
        'subject_id' => $ticket->id,
    ]);
});

test('a ticket returns to open when its only technician is removed', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $ticket = makeSupportTicket($company, $admin, ['status' => TicketStatus::Assigned->value]);
    assignSupportTicket($ticket, $technician);

    $this->actingAs($admin);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->call('unassign', $technician->id)
        ->assertHasNoErrors();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Open->value)
        ->and(TicketAssignmentModel::query()->whereNull('unassigned_at')->count())->toBe(0);
});

test('the last technician can not be removed from an in-progress ticket', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $ticket = makeSupportTicket($company, $admin, ['status' => TicketStatus::InProgress->value]);
    assignSupportTicket($ticket, $technician);

    $this->actingAs($admin);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->call('unassign', $technician->id)
        ->assertHasErrors('assignment');

    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress->value)
        ->and(TicketAssignmentModel::query()->whereNull('unassigned_at')->count())->toBe(1);
});

test('assigned engineers can resolve and close tickets but can not assign or cancel them', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $ticket = makeSupportTicket($company, $admin, ['status' => TicketStatus::InProgress->value]);
    assignSupportTicket($ticket, $engineer);

    $this->actingAs($engineer);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->call('changeStatus', TicketStatus::Resolved->value)
        ->assertHasNoErrors()
        ->call('changeStatus', TicketStatus::Closed->value)
        ->assertHasNoErrors();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Closed->value)
        ->and($engineer->can('assign', $ticket))->toBeFalse()
        ->and($engineer->can('cancel', $ticket))->toBeFalse();
});

test('cancelling a ticket requires a permission and a reason', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $ticket = makeSupportTicket($company, $admin);

    $this->actingAs($admin);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->call('changeStatus', TicketStatus::Cancelled->value)
        ->assertHasErrors('status');

    expect($ticket->refresh()->status)->toBe(TicketStatus::Open->value);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->set('statusReason', 'Solicitud duplicada.')
        ->call('changeStatus', TicketStatus::Cancelled->value)
        ->assertHasNoErrors();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Cancelled->value)
        ->and($ticket->cancellation_reason)->toBe('Solicitud duplicada.')
        ->and($ticket->cancelled_at)->not->toBeNull()
        ->and($technician->can('cancel', $ticket))->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'ticket.cancelled',
        'company_id' => $company->id,
        'subject_id' => $ticket->id,
    ]);
});

test('the cancel action renders with a confirmation instead of raw Blade directives', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $ticket = makeSupportTicket($company, $admin);

    $this->actingAs($admin);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->assertSee('¿Cancelar este ticket?')
        ->assertDontSee('@if', false)
        ->assertDontSee('@endif', false);
});

test('tickets can receive comments and edit their details', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $ticket = makeSupportTicket($company, $admin);

    $this->actingAs($admin);

    Livewire::test(TicketShow::class, ['ticket' => $ticket])
        ->set('comment', 'Se revisó el equipo y se solicitó un repuesto.')
        ->call('addComment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ticket_activities', [
        'ticket_id' => $ticket->id,
        'type' => 'comment',
        'body' => 'Se revisó el equipo y se solicitó un repuesto.',
    ]);

    Livewire::test(TicketForm::class, ['ticket' => $ticket])
        ->set('subject', 'Equipo revisado y pendiente de repuesto')
        ->call('save')
        ->assertHasNoErrors();

    expect($ticket->refresh()->subject)->toBe('Equipo revisado y pendiente de repuesto');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'ticket.updated',
        'company_id' => $company->id,
        'subject_id' => $ticket->id,
    ]);
});

test('ticket listings and details are isolated by company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $ownTicket = makeSupportTicket($company, $admin, ['subject' => 'Ticket visible']);
    $foreignTicket = makeSupportTicket($otherCompany, null, ['subject' => 'Ticket ajeno']);

    $this->actingAs($admin);

    $this->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($ownTicket->folio)
        ->assertDontSee($foreignTicket->folio);

    $this->get(route('tickets.show', $foreignTicket))
        ->assertNotFound();
});

test('ticket dashboard counters ignore resolved and foreign-company tickets', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    makeSupportTicket($company, $admin, [
        'status' => TicketStatus::Open->value,
        'priority' => 'urgent',
    ]);

    $assigned = makeSupportTicket($company, $admin, [
        'status' => TicketStatus::Assigned->value,
        'priority' => 'high',
    ]);
    assignSupportTicket($assigned, $admin);

    makeSupportTicket($company, $admin, [
        'status' => TicketStatus::Resolved->value,
        'priority' => 'urgent',
    ]);

    makeSupportTicket($otherCompany, null, [
        'status' => TicketStatus::Open->value,
        'priority' => 'urgent',
    ]);

    $counts = app(CountDashboardTickets::class)->execute($company->id, $admin->id);

    expect($counts)->toBe([
        'unresolved' => 2,
        'unassigned' => 1,
        'mine' => 1,
        'urgent' => 1,
    ]);
});

test('users without ticket permissions can not open ticket screens', function () {
    $company = createTestCompany();
    $auditor = createTestUser($company, [], RoleName::Auditor);
    $ticket = makeSupportTicket($company);

    $this->actingAs($auditor);

    $this->get(route('tickets.index'))->assertOk();
    $this->get(route('tickets.create'))->assertForbidden();
    $this->get(route('tickets.edit', $ticket))->assertForbidden();
});

test('ticket filters can scope the listing to my tickets', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);

    $mine = makeSupportTicket($company, $admin, ['subject' => 'Asignado a mí']);
    assignSupportTicket($mine, $admin);

    $other = makeSupportTicket($company, $admin, ['subject' => 'Asignado a otro']);
    assignSupportTicket($other, $technician);

    $this->actingAs($admin);

    Livewire::test(TicketIndex::class)
        ->set('scope', 'mine')
        ->assertSee($mine->folio)
        ->assertDontSee($other->folio);
});
