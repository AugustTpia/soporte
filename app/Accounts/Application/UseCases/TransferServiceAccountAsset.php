<?php

namespace App\Accounts\Application\UseCases;

use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class TransferServiceAccountAsset
{
    public function __construct(
        private ServiceAccountRepository $accounts,
        private AssetRepository $assets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $accountId,
        int $fromAssetId,
        int $toAssetId,
        int $actorId,
    ): void {
        $account = $this->accounts->find($accountId, $companyId)
            ?? throw DomainException::because('La cuenta no existe en esta empresa.');

        $account->assertCanLinkAssets();

        $fromLink = $this->accounts->findActiveLink($accountId, $fromAssetId)
            ?? throw DomainException::because('La cuenta no está vinculada al equipo de origen.');

        $toAsset = $this->assets->find($toAssetId, $companyId)
            ?? throw DomainException::because('El equipo destino no existe en esta empresa.');

        if ($fromAssetId === $toAssetId) {
            throw DomainException::because('El equipo destino debe ser distinto del equipo actual.');
        }

        if (! $toAsset->status->isLinkable()) {
            throw DomainException::because('No se puede transferir una cuenta a un equipo retirado o perdido.');
        }

        if ($this->accounts->hasActiveLink($accountId, $toAssetId)) {
            throw DomainException::because('La cuenta ya está vinculada al equipo destino.');
        }

        $reason = 'Sustitución de equipo';

        $this->transactions->run(function () use (
            $account,
            $accountId,
            $fromLink,
            $toAsset,
            $actorId,
            $companyId,
            $reason,
        ): void {
            $this->accounts->closeLink($fromLink->id, $actorId, $reason);
            $this->accounts->recordLink($accountId, (int) $toAsset->id, $actorId, $reason);

            $this->audit->log(
                event: 'service_account.asset_transferred',
                description: "Se transfirió la cuenta {$account->username} del equipo {$fromLink->assetInternalCode} a {$toAsset->internalCode}.",
                subjectType: ServiceAccountModel::class,
                subjectId: $accountId,
                oldValues: [
                    'asset_id' => $fromLink->assetId,
                    'asset_internal_code' => $fromLink->assetInternalCode,
                    'asset_hostname' => $fromLink->assetHostname,
                ],
                newValues: [
                    'asset_id' => $toAsset->id,
                    'asset_internal_code' => $toAsset->internalCode,
                    'asset_hostname' => $toAsset->hostname,
                    'reason' => $reason,
                ],
                companyId: $companyId,
            );
        });
    }
}
