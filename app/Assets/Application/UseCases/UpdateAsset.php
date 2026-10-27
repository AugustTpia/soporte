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

final readonly class UpdateAsset
{
    public function __construct(
        private AssetRepository $assets,
        private LocationRepository $locations,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $assetId, AssetInput $input, int $actorId): Asset
    {
        $asset = $this->assets->find($assetId, $companyId)
            ?? throw DomainException::because('El activo no existe en esta empresa.');

        $this->assertLocation($companyId, $input->locationId, $asset->locationId);
        $this->assertAssignedUser($companyId, $input->assignedUserId, $asset->assignedUserId);

        $internalCode = trim($input->internalCode);
        $hostname = $this->cleanHostname($input->hostname);

        if ($this->assets->internalCodeExists($companyId, $internalCode, $asset->id)) {
            throw DomainException::because('Ya existe un activo con ese código interno en esta empresa.');
        }

        if ($hostname !== null && $this->assets->hostnameExists($companyId, $hostname, $asset->id)) {
            throw DomainException::because('Ya existe un activo con ese hostname en esta empresa.');
        }

        $before = $this->auditValues($asset);
        $oldStatus = $asset->status;
        $assignmentChanged = $asset->locationId !== $input->locationId
            || $asset->assignedUserId !== $input->assignedUserId;

        $asset->updateDetails(
            internalCode: $internalCode,
            hostname: $hostname,
            category: $input->category,
            brand: $input->brand,
            model: $input->model,
            serialNumber: $input->serialNumber,
            locationId: $input->locationId,
            assignedUserId: $input->assignedUserId,
            operatingSystem: $input->operatingSystem,
            ipAddress: $input->ipAddress,
            purchaseDate: $input->purchaseDate,
            warrantyUntil: $input->warrantyUntil,
            notes: $input->notes,
        );

        $statusChanged = $oldStatus !== $input->status;
        $asset->changeStatus($input->status);

        return $this->transactions->run(function () use (
            $asset,
            $companyId,
            $actorId,
            $before,
            $assignmentChanged,
            $statusChanged,
            $oldStatus,
        ): Asset {
            $saved = $this->assets->save($asset);

            if ($assignmentChanged) {
                $this->assets->closeCurrentAssignment((int) $saved->id);
                $this->assets->recordAssignment(
                    assetId: (int) $saved->id,
                    locationId: $saved->locationId,
                    assignedUserId: $saved->assignedUserId,
                    createdBy: $actorId,
                    notes: 'Transferencia de ubicación o responsable',
                );

                $this->audit->log(
                    event: 'asset.transferred',
                    description: "Se transfirió el activo {$saved->internalCode}.",
                    subjectType: AssetModel::class,
                    subjectId: $saved->id,
                    oldValues: [
                        'location_id' => $before['location_id'],
                        'location_name' => $before['location_name'],
                        'assigned_user_id' => $before['assigned_user_id'],
                        'assigned_user_name' => $before['assigned_user_name'],
                    ],
                    newValues: [
                        'location_id' => $saved->locationId,
                        'location_name' => $saved->locationName,
                        'assigned_user_id' => $saved->assignedUserId,
                        'assigned_user_name' => $saved->assignedUserName,
                    ],
                    companyId: $companyId,
                );
            }

            if ($statusChanged) {
                $this->audit->log(
                    event: 'asset.status_changed',
                    description: "Se cambió el estado del activo {$saved->internalCode}.",
                    subjectType: AssetModel::class,
                    subjectId: $saved->id,
                    oldValues: ['status' => $oldStatus->value],
                    newValues: ['status' => $saved->status->value],
                    companyId: $companyId,
                );
            }

            $this->audit->log(
                event: 'asset.updated',
                description: "Se actualizó el activo {$saved->internalCode}.",
                subjectType: AssetModel::class,
                subjectId: $saved->id,
                oldValues: $before,
                newValues: $this->auditValues($saved),
                companyId: $companyId,
            );

            return $saved;
        });
    }

    private function assertLocation(int $companyId, int $locationId, int $currentLocationId): void
    {
        $location = $this->locations->find($locationId, $companyId);

        if ($location === null) {
            throw DomainException::because('La ubicación seleccionada no existe en esta empresa.');
        }

        if (! $location->isActive && $locationId !== $currentLocationId) {
            throw DomainException::because('La ubicación seleccionada está inactiva.');
        }
    }

    private function assertAssignedUser(int $companyId, ?int $userId, ?int $currentUserId): void
    {
        if ($userId === null) {
            return;
        }

        $user = $this->users->find($userId);

        if ($user === null || $user->companyId !== $companyId) {
            throw DomainException::because('El responsable seleccionado no pertenece a esta empresa.');
        }

        if (! $user->isActive() && $userId !== $currentUserId) {
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
