<?php

namespace App\Contacts\Application\UseCases;

use App\Contacts\Domain\Entities\ClientContact;
use App\Contacts\Domain\Repositories\ClientContactRepository;
use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class ChangeClientContactStatus
{
    public function __construct(
        private ClientContactRepository $contacts,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $contactId, bool $active): ClientContact
    {
        $contact = $this->contacts->find($contactId, $companyId)
            ?? throw DomainException::because('El contacto no existe en esta empresa.');

        if ($contact->isActive === $active) {
            return $contact;
        }

        $before = [
            'is_active' => $contact->isActive,
            'is_primary' => $contact->isPrimary,
        ];

        return $this->transactions->run(function () use ($companyId, $contact, $active, $before): ClientContact {
            if ($active) {
                $contact->updateDetails(
                    name: $contact->name,
                    locationId: $contact->locationId,
                    position: $contact->position,
                    email: $contact->email,
                    phone: $contact->phone,
                    whatsapp: $contact->whatsapp,
                    isPrimary: $contact->isPrimary,
                    notes: $contact->notes,
                    isActive: true,
                );
            } else {
                $contact->deactivate();
            }

            $saved = $this->contacts->save($contact);

            $this->audit->log(
                event: $active ? 'client_contact.activated' : 'client_contact.deactivated',
                description: $active
                    ? "Se activó el contacto {$saved->name}."
                    : "Se desactivó el contacto {$saved->name}.",
                subjectType: ClientContactModel::class,
                subjectId: $saved->id,
                oldValues: $before,
                newValues: ['is_active' => $saved->isActive, 'is_primary' => $saved->isPrimary],
                companyId: $companyId,
            );

            return $saved;
        });
    }
}
