<?php

namespace App\Assets\Application\UseCases;

use App\Assets\Application\DTOs\AssetInput;
use App\Assets\Domain\Entities\Asset;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Identity\Domain\Repositories\UserRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class CreateAsset
{
    public function __construct(
        private AssetRepository $assets,
        private LocationRepository $locations,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, AssetInput $input, int $actorId): Asset
    {
        $this->assertLocation($companyId, $input->locationId);
        $this->assertAssignedUser($companyId, $input->assignedUserId);

        $internalCode = trim($input->internalCode);
        $hostname = $this->cleanHostname($input->hostname);

        if ($this->assets->internalCodeExists($companyId, $internalCode)) {
            throw DomainException::because('Ya existe un activo con ese código interno en esta empresa.');
        }

        if ($hostname !== null && $this->assets->hostnameExists($companyId, $hostname)) {
            throw DomainException::because('Ya existe un activo con ese hostname en esta empresa.');
        }

        $asset = Asset::create(
            companyId: $companyId,
            internalCode: $internalCode,
            category: $input->category,
            locationId: $input->locationId,
            hostname: $hostname,
            brand: $input->brand,
            model: $input->model,
            serialNumber: $input->serialNumber,
            assignedUserId: $input->assignedUserId,
            operatingSystem: $input->operatingSystem,
            ipAddress: $input->ipAddress,
            purchaseDate: $input->purchaseDate,
            warrantyUntil: $input->warrantyUntil,
            notes: $input->notes,
            createdBy: $actorId,
        );

        return $this->transactions->run(function () use ($asset, $companyId, $actorId): Asset {
            $saved = $this->assets->save($asset);

            $this->assets->recordAssignment(
                assetId: (int) $saved->id,
                locationId: $saved->locationId,
                assignedUserId: $saved->assignedUserId,
                createdBy: $actorId,
                notes: 'Asignación inicial',
            );

            $this->audit->log(
                event: 'asset.created',
                description: "Se creó el activo {$saved->internalCode}.",
                subjectType: AssetModel::class,
                subjectId: $saved->id,
                newValues: $this->auditValues($saved),
                companyId: $companyId,
            );

            return $saved;
        });
    }

    private function assertLocation(int $companyId, int $locationId): void
    {
        $location = $this->locations->find($locationId, $companyId);

        if ($location === null) {
            throw DomainException::because('La ubicación seleccionada no existe en esta empresa.');
        }

        if (! $location->isActive) {
            throw DomainException::because('La ubicación seleccionada está inactiva.');
        }
    }

    private function assertAssignedUser(int $companyId, ?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $user = $this->users->find($userId);

        if ($user === null || $user->companyId !== $companyId) {
            throw DomainException::because('El responsable seleccionado no pertenece a esta empresa.');
        }

        if (! $user->isActive()) {
            throw DomainException::because('El responsable seleccionado está inactivo.');
        }
    }

    private function cleanHostname(?string $hostname): ?string
    {
        $hostname = trim((string) $hostname);

        return $hostname === '' ? null : mb_strtolower($hostname);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Asset $asset): array
    {
        return [
            'internal_code' => $asset->internalCode,
            'hostname' => $asset->hostname,
            'category' => $asset->category->value,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'serial_number' => $asset->serialNumber,
            'location_id' => $asset->locationId,
            'location_name' => $asset->locationName,
            'assigned_user_id' => $asset->assignedUserId,
            'assigned_user_name' => $asset->assignedUserName,
            'status' => $asset->status->value,
            'operating_system' => $asset->operatingSystem,
            'ip_address' => $asset->ipAddress,
            'purchase_date' => $asset->purchaseDate?->format('Y-m-d'),
            'warranty_until' => $asset->warrantyUntil?->format('Y-m-d'),
            'notes' => $asset->notes,
        ];
    }
}
