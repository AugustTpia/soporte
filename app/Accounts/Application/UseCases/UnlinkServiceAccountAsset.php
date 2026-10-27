<?php

namespace App\Accounts\Application\UseCases;

use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UnlinkServiceAccountAsset
{
    public function __construct(
        private ServiceAccountRepository $accounts,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $accountId, int $linkId, int $actorId): void
    {
        $account = $this->accounts->find($accountId, $companyId)
            ?? throw DomainException::because('La cuenta no existe en esta empresa.');

        $link = $this->accounts->findLink($linkId, $accountId)
            ?? throw DomainException::because('El vínculo con el equipo no existe en esta cuenta.');

        if (! $link->isActive()) {
            throw DomainException::because('El vínculo con el equipo ya fue cerrado.');
        }

        $reason = 'Desvinculación manual';

        $this->transactions->run(function () use ($account, $accountId, $linkId, $actorId, $companyId, $link, $reason): void {
            $this->accounts->closeLink($linkId, $actorId, $reason);

            $this->audit->log(
                event: 'service_account.asset_unlinked',
                description: "Se desvinculó la cuenta {$account->username} del equipo {$link->assetInternalCode}.",
                subjectType: ServiceAccountModel::class,
                subjectId: $accountId,
                oldValues: [
                    'link_id' => $link->id,
                    'asset_id' => $link->assetId,
                    'asset_internal_code' => $link->assetInternalCode,
                    'asset_hostname' => $link->assetHostname,
                    'linked_at' => $link->linkedAt->format('Y-m-d H:i:s'),
                    'reason' => $reason,
                ],
                companyId: $companyId,
            );
        });
    }
}
