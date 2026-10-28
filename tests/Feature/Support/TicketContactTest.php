<?php

use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use App\Identity\Domain\Enums\RoleName;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use App\Support\Presentation\Livewire\Tickets\TicketForm;
use Livewire\Livewire;

test('selecting a contact fills requester data and stores a snapshot', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $contact = ClientContactModel::factory()->forCompany($company)->create([
        'name' => 'María López',
        'email' => 'maria@example.test',
        'phone' => '5511112222',
    ]);

    $this->actingAs($admin);

    Livewire::test(TicketForm::class)
        ->set('category', 'hardware')
        ->set('priority', 'medium')
        ->set('subject', 'Equipo sin acceso')
        ->set('description', 'La computadora no inicia sesión.')
        ->set('clientContactId', (string) $contact->id)
        ->assertSet('requesterName', 'María López')
        ->assertSet('requesterContact', 'maria@example.test')
        ->call('save')
        ->assertHasNoErrors();

    $ticket = SupportTicketModel::query()->firstOrFail();

    expect($ticket->client_contact_id)->toBe($contact->id)
        ->and($ticket->requester_name)->toBe('María López')
        ->and($ticket->requester_contact)->toBe('maria@example.test');
});

test('ticket snapshots do not change when the contact is edited', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $contact = ClientContactModel::factory()->forCompany($company)->create([
        'name' => 'Nombre original',
        'email' => 'original@example.test',
    ]);

    $this->actingAs($admin);

    Livewire::test(TicketForm::class)
        ->set('category', 'hardware')
        ->set('priority', 'medium')
        ->set('subject', 'Ticket histórico')
        ->set('description', 'Descripción')
        ->set('clientContactId', (string) $contact->id)
        ->call('save')
        ->assertHasNoErrors();

    $contact->update([
        'name' => 'Nombre nuevo',
        'email' => 'nuevo@example.test',
    ]);

    $ticket = SupportTicketModel::query()->firstOrFail();

    expect($ticket->requester_name)->toBe('Nombre original')
        ->and($ticket->requester_contact)->toBe('original@example.test');
});

test('inactive contacts cannot be selected for new tickets', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $contact = ClientContactModel::factory()->forCompany($company)->create([
        'is_active' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test(TicketForm::class)
        ->set('category', 'hardware')
        ->set('priority', 'medium')
        ->set('subject', 'Ticket nuevo')
        ->set('description', 'Descripción')
        ->set('clientContactId', (string) $contact->id)
        ->call('save')
        ->assertHasErrors(['clientContactId']);

    $this->assertDatabaseCount('support_tickets', 0);
});

test('an existing ticket keeps its inactive contact when edited', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $contact = ClientContactModel::factory()->forCompany($company)->create([
        'name' => 'Contacto retirado',
        'email' => 'retirado@example.test',
        'is_active' => false,
    ]);
    $ticket = SupportTicketModel::factory()->forCompany($company, $admin)->create([
        'client_contact_id' => $contact->id,
        'requester_name' => $contact->name,
        'requester_contact' => $contact->email,
    ]);

    $this->actingAs($admin);

    Livewire::test(TicketForm::class, ['ticket' => $ticket])
        ->set('subject', 'Asunto corregido')
        ->call('save')
        ->assertHasNoErrors();

    expect($ticket->refresh())
        ->subject->toBe('Asunto corregido')
        ->client_contact_id->toBe($contact->id);
});

test('tickets cannot link contacts from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $foreignContact = ClientContactModel::factory()->forCompany($otherCompany)->create();

    $this->actingAs($admin);

    Livewire::test(TicketForm::class)
        ->set('category', 'hardware')
        ->set('priority', 'medium')
        ->set('subject', 'Ticket nuevo')
        ->set('description', 'Descripción')
        ->set('clientContactId', (string) $foreignContact->id)
        ->call('save')
        ->assertHasErrors(['clientContactId']);
});

test('ticket detail shows the linked contact name', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $contact = ClientContactModel::factory()->forCompany($company)->create([
        'name' => 'Contacto en Ticket',
    ]);
    $ticket = SupportTicketModel::factory()->forCompany($company, $admin)->create([
        'client_contact_id' => $contact->id,
    ]);

    $this->actingAs($admin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Contacto en Ticket');
});
