<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UpdateMaintenanceExecutionChecklist
{
    public function __construct(
        private MaintenanceExecutionRepository $executions,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    /**
     * @param  array<int, array{id: int, is_completed: bool, notes: ?string}>  $states
     */
    public function execute(int $companyId, int $executionId, array $states, int $actorId): MaintenanceExecution
    {
        $execution = $this->executions->find($executionId, $companyId)
            ?? throw DomainException::because('La ejecución de mantenimiento no existe en esta empresa.');

        foreach ($states as $state) {
            $execution->updateChecklistItem(
                itemId: $state['id'],
                isCompleted: $state['is_completed'],
                notes: $state['notes'],
                actorId: $actorId,
            );
        }

        return $this->transactions->run(function () use ($execution): MaintenanceExecution {
            $saved = $this->executions->save($execution);

            $this->audit->log(
                event: 'maintenance_execution.checklist_updated',
                description: "Se actualizó el checklist de {$saved->targetLabel}.",
                subjectType: MaintenanceExecutionModel::class,
                subjectId: $saved->id,
                newValues: [
                    'checklist_completed' => count(array_filter(
                        $saved->checklistItems,
                        fn ($item) => $item->isCompleted,
                    )),
                    'checklist_total' => count($saved->checklistItems),
                ],
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }
}
