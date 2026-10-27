<?php

namespace App\Assets\Application\UseCases;

use App\Assets\Domain\Entities\Asset;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class ChangeAssetStatus
{
    public function __construct(
        private AssetRepository $assets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $assetId, AssetStatus $status): Asset
    {
        $asset = $this->assets->find($assetId, $companyId)
            ?? throw DomainException::because('El activo no existe en esta empresa.');

        $oldStatus = $asset->status;
        $asset->changeStatus($status);

        return $this->transactions->run(function () use ($asset, $companyId, $oldStatus): Asset {
            $saved = $this->assets->save($asset);

            $this->audit->log(
                event: 'asset.status_changed',
                description: "Se cambió el estado del activo {$saved->internalCode}.",
                subjectType: AssetModel::class,
                subjectId: $saved->id,
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => $saved->status->value],
                companyId: $companyId,
            );

            return $saved;
        });
    }
}
