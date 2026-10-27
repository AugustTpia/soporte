<?php

namespace App\Accounts\Application\UseCases;

use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class LinkServiceAccountAsset
{
    public function __construct(
        private ServiceAccountRepository $accounts,
        private AssetRepository $assets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $accountId, int $assetId, int $actorId): void
    {
        $account = $this->accounts->find($accountId, $companyId)
            ?? throw DomainException::because('La cuenta no existe en esta empresa.');

        $account->assertCanLinkAssets();

        $asset = $this->assets->find($assetId, $companyId)
            ?? throw DomainException::because('El equipo seleccionado no existe en esta empresa.');

        if (! $asset->status->isLinkable()) {
            throw DomainException::because('No se puede vincular una cuenta a un equipo retirado o perdido.');
        }

        if ($this->accounts->hasActiveLink($accountId, $assetId)) {
            throw DomainException::because('La cuenta ya está vinculada a ese equipo.');
        }

        $this->transactions->run(function () use ($account, $accountId, $assetId, $actorId, $companyId, $asset): void {
            $this->accounts->recordLink(
                serviceAccountId: $accountId,
                assetId: $assetId,
                actorId: $actorId,
                reason: 'Vinculación manual',
            );

            $this->audit->log(
                event: 'service_account.asset_linked',
                description: "Se vinculó la cuenta {$account->username} al equipo {$asset->internalCode}.",
                subjectType: ServiceAccountModel::class,
                subjectId: $accountId,
                newValues: [
                    'asset_id' => $asset->id,
                    'asset_internal_code' => $asset->internalCode,
                    'asset_hostname' => $asset->hostname,
                    'asset_status' => $asset->status->value,
                ],
                companyId: $companyId,
            );
        });
    }
}
