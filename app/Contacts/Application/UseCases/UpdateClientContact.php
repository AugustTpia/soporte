<?php

namespace App\Contacts\Application\UseCases;

use App\Contacts\Application\DTOs\ClientContactInput;
use App\Contacts\Domain\Entities\ClientContact;
use App\Contacts\Domain\Repositories\ClientContactRepository;
use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UpdateClientContact
{
    public function __construct(
        private ClientContactRepository $contacts,
        private LocationRepository $locations,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $contactId, ClientContactInput $input): ClientContact
    {
        $contact = $this->contacts->find($contactId, $companyId)
            ?? throw DomainException::because('El contacto no existe en esta empresa.');

        $this->assertLocation($companyId, $input->locationId);

        $before = $this->auditValues($contact);

        return $this->transactions->run(function () use ($companyId, $contact, $input, $before): ClientContact {
            if ($input->isPrimary && (! $contact->isPrimary || $contact->isActive !== $input->isActive)) {
                $this->contacts->clearPrimary($companyId, $contact->id);
            }

            $contact->updateDetails(
                name: $input->name,
                locationId: $input->locationId,
                position: $input->position,
                email: $input->email,
                phone: $input->phone,
                whatsapp: $input->whatsapp,
                isPrimary: $input->isPrimary,
                notes: $input->notes,
                isActive: $input->isActive,
            );

            $saved = $this->contacts->save($contact);

            $event = match (true) {
                $before['is_active'] === true && ! $saved->isActive => 'client_contact.deactivated',
                $before['is_active'] === false && $saved->isActive => 'client_contact.activated',
                default => 'client_contact.updated',
            };

            $this->audit->log(
                event: $event,
                description: $event === 'client_contact.deactivated'
                    ? "Se desactivó el contacto {$saved->name}."
                    : ($event === 'client_contact.activated'
                        ? "Se activó el contacto {$saved->name}."
                        : "Se actualizó el contacto {$saved->name}."),
                subjectType: ClientContactModel::class,
                subjectId: $saved->id,
                oldValues: $before,
                newValues: $this->auditValues($saved),
                companyId: $companyId,
            );

            return $saved;
        });
    }

    private function assertLocation(int $companyId, ?int $locationId): void
    {
        if ($locationId !== null && $this->locations->find($locationId, $companyId) === null) {
            throw DomainException::because('La ubicación seleccionada no pertenece a esta empresa.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(ClientContact $contact): array
    {
        return [
            'name' => $contact->name,
            'location_id' => $contact->locationId,
            'position' => $contact->position,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'whatsapp' => $contact->whatsapp,
            'is_primary' => $contact->isPrimary,
            'is_active' => $contact->isActive,
            'notes' => $contact->notes,
        ];
    }
}
