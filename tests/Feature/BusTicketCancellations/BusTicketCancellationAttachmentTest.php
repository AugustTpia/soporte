<?php

use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationAttachmentModel;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\BusTicketCancellations\Presentation\Livewire\BusTicketCancellationAttachments;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

function makeBusCancellationAttachmentFor(
    CompanyModel $company,
    User $admin,
    BusTicketCancellationModel $cancellation,
): BusTicketCancellationAttachmentModel {
    Livewire::actingAs($admin)
        ->test(BusTicketCancellationAttachments::class, ['cancellationId' => $cancellation->id])
        ->set('file', UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    return BusTicketCancellationAttachmentModel::query()->firstOrFail();
}

test('administrators can attach private evidence to a bus ticket cancellation', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $cancellation = makeBusCancellationFor($company, $admin);

    $this->actingAs($admin);

    Livewire::test(BusTicketCancellationAttachments::class, ['cancellationId' => $cancellation->id])
        ->set('file', UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    $attachment = BusTicketCancellationAttachmentModel::query()->firstOrFail();

    expect($attachment->company_id)->toBe($company->id)
        ->and($attachment->bus_ticket_cancellation_id)->toBe($cancellation->id)
        ->and($attachment->original_name)->toBe('comprobante.pdf')
        ->and($attachment->mime_type)->toBe('application/pdf')
        ->and($attachment->uploaded_by)->toBe($admin->id)
        ->and($attachment->size_bytes)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($attachment->path);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'bus_ticket_cancellation_attachment.uploaded',
        'company_id' => $company->id,
        'subject_id' => $attachment->id,
    ]);
});

test('unsafe or unsupported evidence files are rejected', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $cancellation = makeBusCancellationFor($company, $admin);

    $this->actingAs($admin);

    Livewire::test(BusTicketCancellationAttachments::class, ['cancellationId' => $cancellation->id])
        ->set('file', UploadedFile::fake()->create('malware.php', 10, 'text/x-php'))
        ->call('upload')
        ->assertHasErrors('file');

    $this->assertDatabaseCount('bus_ticket_cancellation_attachments', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('authorized users can download evidence and the access is audited', function () {
    $company = createTestCompany();
    enableBusTicketFeature($company);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $cancellation = makeBusCancellationFor($company, $admin);
    $attachment = makeBusCancellationAttachmentFor($company, $admin, $cancellation);

    $this->actingAs($engineer);

    $this->get(route('bus-ticket-cancellations.attachments.download', [
        'cancellation' => $cancellation->id,
        'attachment' => $attachment->id,
    ]))
        ->assertOk()
        ->assertDownload('comprobante.pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'bus_ticket_cancellation_attachment.downloaded',
        'company_id' => $company->id,
        'subject_id' => $attachment->id,
    ]);
});

test('evidence downloads are isolated by company and cancellation', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    enableBusTicketFeature($company);
    enableBusTicketFeature($otherCompany);
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherAdmin = createTestUser($otherCompany, [], RoleName::CompanyAdmin);
    $cancellation = makeBusCancellationFor($company, $admin);
    $otherCancellation = makeBusCancellationFor($otherCompany, $otherAdmin);
    $foreignAttachment = makeBusCancellationAttachmentFor($otherCompany, $otherAdmin, $otherCancellation);

    $this->actingAs($admin);

    $this->get(route('bus-ticket-cancellations.attachments.download', [
        'cancellation' => $cancellation->id,
        'attachment' => $foreignAttachment->id,
    ]))->assertNotFound();

    $this->get(route('bus-ticket-cancellations.attachments.download', [
        'cancellation' => $otherCancellation->id,
        'attachment' => $foreignAttachment->id,
    ]))->assertNotFound();
});
