<?php

namespace App\Locations\Application\UseCases;

use App\Locations\Domain\Entities\Location;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class ToggleLocationStatus
{
    public function __construct(
        private LocationRepository $locations,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, int $locationId): Location
    {
        $location = $this->locations->find($locationId, $companyId)
            ?? throw DomainException::because('La ubicación no existe en esta empresa.');

        $location->isActive ? $location->deactivate() : $location->activate();
        $saved = $this->locations->save($location);

        $this->audit->log(
            event: 'location.status_changed',
            description: "Se cambió el estado de la ubicación {$saved->name}.",
            subjectType: LocationModel::class,
            subjectId: $saved->id,
            oldValues: ['is_active' => ! $saved->isActive],
            newValues: ['is_active' => $saved->isActive],
            companyId: $companyId,
        );

        return $saved;
    }
}
