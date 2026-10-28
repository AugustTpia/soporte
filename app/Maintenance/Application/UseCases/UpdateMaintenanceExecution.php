<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Application\DTOs\MaintenanceExecutionUpdateInput;
use App\Maintenance\Application\Services\MaintenanceTargetResolver;
use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UpdateMaintenanceExecution
{
    public function __construct(
        private MaintenanceExecutionRepository $executions,
        private MaintenanceTargetResolver $targets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $executionId,
        MaintenanceExecutionUpdateInput $input,
    ): MaintenanceExecution {
        $execution = $this->executions->find($executionId, $companyId)
            ?? throw DomainException::because('La ejecución de mantenimiento no existe en esta empresa.');

        $this->targets->assertTechnician($companyId, $input->technicianId);
        $old = $execution->auditValues();

        $execution->updateDetails(
            scheduledAt: $input->scheduledAt,
            technicianId: $input->technicianId,
            findings: $input->findings,
            actions: $input->actions,
            recommendations: $input->recommendations,
            partsUsed: $input->partsUsed,
            timeSpentMinutes: $input->timeSpentMinutes,
        );

        return $this->transactions->run(function () use ($execution, $old): MaintenanceExecution {
            $saved = $this->executions->save($execution);

            $this->audit->log(
                event: 'maintenance_execution.updated',
                description: "Se actualizó la ejecución de mantenimiento de {$saved->targetLabel}.",
                subjectType: MaintenanceExecutionModel::class,
                subjectId: $saved->id,
                oldValues: $old,
                newValues: $saved->auditValues(),
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }
}
