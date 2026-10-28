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

final readonly class CreateClientContact
{
    public function __construct(
        private ClientContactRepository $contacts,
        private LocationRepository $locations,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, ClientContactInput $input): ClientContact
    {
        $this->assertLocation($companyId, $input->locationId);

        return $this->transactions->run(function () use ($companyId, $input): ClientContact {
            if ($input->isPrimary) {
                $this->contacts->clearPrimary($companyId);
            }

            $contact = ClientContact::create(
                companyId: $companyId,
                name: $input->name,
                locationId: $input->locationId,
                position: $input->position,
                email: $input->email,
                phone: $input->phone,
                whatsapp: $input->whatsapp,
                isPrimary: $input->isPrimary,
                notes: $input->notes,
            );

            if (! $input->isActive) {
                $contact->deactivate();
            }

            $saved = $this->contacts->save($contact);

            $this->audit->log(
                event: 'client_contact.created',
                description: "Se creó el contacto {$saved->name}.",
                subjectType: ClientContactModel::class,
                subjectId: $saved->id,
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
