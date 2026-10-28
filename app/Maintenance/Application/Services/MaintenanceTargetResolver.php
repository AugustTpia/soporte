<?php

namespace App\Maintenance\Application\Services;

use App\Assets\Domain\Repositories\AssetRepository;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Repositories\UserRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Maintenance\Application\DTOs\MaintenanceResolvedTarget;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class MaintenanceTargetResolver
{
    public function __construct(
        private AssetRepository $assets,
        private LocationRepository $locations,
        private CompanyRepository $companies,
        private UserRepository $users,
    ) {}

    public function resolve(
        int $companyId,
        MaintenanceTargetType $targetType,
        ?int $assetId,
        ?int $locationId,
    ): MaintenanceResolvedTarget {
        if ($targetType === MaintenanceTargetType::Asset) {
            if ($assetId === null || $assetId <= 0) {
                throw DomainException::because('Selecciona un activo para el mantenimiento.');
            }

            $asset = $this->assets->find($assetId, $companyId);

            if ($asset === null) {
                throw DomainException::because('El activo seleccionado no pertenece a esta empresa.');
            }

            return new MaintenanceResolvedTarget(
                type: $targetType,
                assetId: $asset->id,
                locationId: null,
                label: $asset->internalCode.($asset->hostname !== null ? ' · '.$asset->hostname : ''),
            );
        }

        if ($targetType === MaintenanceTargetType::Location) {
            if ($locationId === null || $locationId <= 0) {
                throw DomainException::because('Selecciona una ubicación para el mantenimiento.');
            }

            $location = $this->locations->find($locationId, $companyId);

            if ($location === null) {
                throw DomainException::because('La ubicación seleccionada no pertenece a esta empresa.');
            }

            return new MaintenanceResolvedTarget(
                type: $targetType,
                assetId: null,
                locationId: $location->id,
                label: $location->name,
            );
        }

        $company = $this->companies->find($companyId);

        if ($company === null) {
            throw DomainException::because('La empresa activa no existe.');
        }

        return new MaintenanceResolvedTarget(
            type: $targetType,
            assetId: null,
            locationId: null,
            label: $company->name,
        );
    }

    public function assertTechnician(int $companyId, int $userId): void
    {
        $user = $this->users->find($userId);

        if ($user === null || $user->companyId !== $companyId) {
            throw DomainException::because('El técnico seleccionado no pertenece a esta empresa.');
        }
    }
}
