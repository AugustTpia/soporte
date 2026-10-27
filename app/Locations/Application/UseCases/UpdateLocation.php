<?php

namespace App\Locations\Application\UseCases;

use App\Locations\Application\DTOs\LocationInput;
use App\Locations\Domain\Entities\Location;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;
use Illuminate\Support\Str;

final readonly class UpdateLocation
{
    public function __construct(
        private LocationRepository $locations,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, int $locationId, LocationInput $input): Location
    {
        $location = $this->locations->find($locationId, $companyId)
            ?? throw DomainException::because('La ubicación no existe en esta empresa.');

        $slug = Str::slug($input->name);

        if ($this->locations->slugExists($companyId, $slug, $location->id)) {
            throw DomainException::because('Ya existe una ubicación con un nombre similar en esta empresa.');
        }

        $before = $this->auditValues($location);

        $location->updateDetails(
            name: $input->name,
            slug: $slug,
            street: $input->street,
            exteriorNumber: $input->exteriorNumber,
            city: $input->city,
            country: $input->country,
            interiorNumber: $input->interiorNumber,
            neighborhood: $input->neighborhood,
            state: $input->state,
            postalCode: $input->postalCode,
            phone: $input->phone,
            notes: $input->notes,
        );

        $input->isActive ? $location->activate() : $location->deactivate();

        $saved = $this->locations->save($location);

        $this->audit->log(
            event: 'location.updated',
            description: "Se actualizó la ubicación {$saved->name}.",
            subjectType: LocationModel::class,
            subjectId: $saved->id,
            oldValues: $before,
            newValues: $this->auditValues($saved),
            companyId: $companyId,
        );

        return $saved;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Location $location): array
    {
        return [
            'name' => $location->name,
            'slug' => $location->slug,
            'street' => $location->street,
            'exterior_number' => $location->exteriorNumber,
            'interior_number' => $location->interiorNumber,
            'neighborhood' => $location->neighborhood,
            'city' => $location->city,
            'state' => $location->state,
            'postal_code' => $location->postalCode,
            'country' => $location->country,
            'phone' => $location->phone,
            'notes' => $location->notes,
            'is_active' => $location->isActive,
        ];
    }
}
