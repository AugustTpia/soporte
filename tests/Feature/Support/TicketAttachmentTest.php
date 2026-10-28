<?php

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAttachmentModel;
use App\Support\Presentation\Livewire\Tickets\TicketAttachments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

function makeTicketAttachmentFor(User $admin, SupportTicketModel $ticket): TicketAttachmentModel
{
    Livewire::actingAs($admin)
        ->test(TicketAttachments::class, ['ticketId' => $ticket->id])
        ->set('file', UploadedFile::fake()->create('evidencia.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    return TicketAttachmentModel::query()->firstOrFail();
}

test('ticket managers can attach private evidence to a ticket', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $ticket = SupportTicketModel::factory()->forCompany($company, $admin)->create();

    $this->actingAs($admin);

    Livewire::test(TicketAttachments::class, ['ticketId' => $ticket->id])
        ->set('file', UploadedFile::fake()->create('evidencia.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    $attachment = TicketAttachmentModel::query()->firstOrFail();

    expect($attachment->company_id)->toBe($company->id)
        ->and($attachment->support_ticket_id)->toBe($ticket->id)
        ->and($attachment->original_name)->toBe('evidencia.pdf')
        ->and($attachment->mime_type)->toBe('application/pdf')
        ->and($attachment->uploaded_by)->toBe($admin->id)
        ->and($attachment->size_bytes)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($attachment->path);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'ticket_attachment.uploaded',
        'company_id' => $company->id,
        'subject_id' => $attachment->id,
    ]);
});

test('unsafe ticket attachments are rejected', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $ticket = SupportTicketModel::factory()->forCompany($company, $admin)->create();

    $this->actingAs($admin);

    Livewire::test(TicketAttachments::class, ['ticketId' => $ticket->id])
        ->set('file', UploadedFile::fake()->create('script.php', 10, 'text/x-php'))
        ->call('upload')
        ->assertHasErrors('file');

    $this->assertDatabaseCount('ticket_attachments', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('read-only users can view attachments but can not upload them', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $auditor = createTestUser($company, [], RoleName::Auditor);
    $ticket = SupportTicketModel::factory()->forCompany($company, $admin)->create();

    $this->actingAs($auditor);

    Livewire::test(TicketAttachments::class, ['ticketId' => $ticket->id])
        ->set('file', UploadedFile::fake()->create('evidencia.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertStatus(403);
});

test('authorized ticket viewers can download attachments and the access is audited', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $ticket = SupportTicketModel::factory()->forCompany($company, $admin)->create();
    $attachment = makeTicketAttachmentFor($admin, $ticket);

    $this->actingAs($engineer);

    $this->get(route('tickets.attachments.download', [
        'ticket' => $ticket->id,
        'attachment' => $attachment->id,
    ]))
        ->assertOk()
        ->assertDownload('evidencia.pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'ticket_attachment.downloaded',
        'company_id' => $company->id,
        'subject_id' => $attachment->id,
    ]);
});

test('ticket attachment downloads are isolated by company and ticket', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherAdmin = createTestUser($otherCompany, [], RoleName::CompanyAdmin);
    $ticket = SupportTicketModel::factory()->forCompany($company, $admin)->create();
    $otherTicket = SupportTicketModel::factory()->forCompany($otherCompany, $otherAdmin)->create();
    $foreignAttachment = makeTicketAttachmentFor($otherAdmin, $otherTicket);

    $this->actingAs($admin);

    $this->get(route('tickets.attachments.download', [
        'ticket' => $ticket->id,
        'attachment' => $foreignAttachment->id,
    ]))->assertNotFound();

    $this->get(route('tickets.attachments.download', [
        'ticket' => $otherTicket->id,
        'attachment' => $foreignAttachment->id,
    ]))->assertNotFound();
});
