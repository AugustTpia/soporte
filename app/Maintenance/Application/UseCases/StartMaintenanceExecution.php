<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final readonly class StartMaintenanceExecution
{
    public function __construct(
        private MaintenanceExecutionRepository $executions,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $executionId): MaintenanceExecution
    {
        $execution = $this->executions->find($executionId, $companyId)
            ?? throw DomainException::because('La ejecución de mantenimiento no existe en esta empresa.');

        $old = $execution->status;
        $execution->start(new DateTimeImmutable);

        return $this->transactions->run(function () use ($execution, $old): MaintenanceExecution {
            $saved = $this->executions->save($execution);

            $this->audit->log(
                event: 'maintenance_execution.started',
                description: "Se inició el mantenimiento de {$saved->targetLabel}.",
                subjectType: MaintenanceExecutionModel::class,
                subjectId: $saved->id,
                oldValues: ['status' => $old->value],
                newValues: ['status' => $saved->status->value],
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }
}
