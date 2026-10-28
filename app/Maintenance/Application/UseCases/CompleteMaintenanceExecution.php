<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final readonly class CompleteMaintenanceExecution
{
    public function __construct(
        private MaintenanceExecutionRepository $executions,
        private MaintenancePlanRepository $plans,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $executionId): MaintenanceExecution
    {
        $execution = $this->executions->find($executionId, $companyId)
            ?? throw DomainException::because('La ejecución de mantenimiento no existe en esta empresa.');

        $old = $execution->status;
        $completedAt = new DateTimeImmutable;
        $execution->complete($completedAt);

        return $this->transactions->run(function () use ($execution, $old, $completedAt): MaintenanceExecution {
            $saved = $this->executions->save($execution);

            $this->audit->log(
                event: 'maintenance_execution.completed',
                description: "Se completó el mantenimiento de {$saved->targetLabel}.",
                subjectType: MaintenanceExecutionModel::class,
                subjectId: $saved->id,
                oldValues: ['status' => $old->value],
                newValues: $saved->auditValues(),
                companyId: $saved->companyId,
            );

            if ($saved->planId !== null) {
                $plan = $this->plans->find($saved->planId, $saved->companyId);

                if ($plan === null) {
                    throw DomainException::because('El plan vinculado ya no existe en esta empresa.');
                }

                $plan->markCompleted($completedAt);
                $updatedPlan = $this->plans->save($plan);

                $this->audit->log(
                    event: 'maintenance_plan.advanced',
                    description: "El plan {$updatedPlan->title} se reprogramó para {$updatedPlan->nextDueAt->format('d/m/Y')}.",
                    subjectType: MaintenancePlanModel::class,
                    subjectId: $updatedPlan->id,
                    newValues: [
                        'last_completed_at' => $updatedPlan->lastCompletedAt?->format('Y-m-d H:i:s'),
                        'next_due_at' => $updatedPlan->nextDueAt->format('Y-m-d H:i:s'),
                    ],
                    companyId: $updatedPlan->companyId,
                );
            }

            return $saved;
        });
    }
}
