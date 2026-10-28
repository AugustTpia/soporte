<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Application\DTOs\MaintenanceExecutionInput;
use App\Maintenance\Application\DTOs\MaintenanceResolvedTarget;
use App\Maintenance\Application\Services\MaintenanceTargetResolver;
use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Entities\MaintenanceExecutionChecklistItem;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Repositories\TicketRepository;

final readonly class ScheduleMaintenanceExecution
{
    public function __construct(
        private MaintenancePlanRepository $plans,
        private MaintenanceExecutionRepository $executions,
        private MaintenanceTargetResolver $targets,
        private TicketRepository $tickets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, MaintenanceExecutionInput $input, int $actorId): MaintenanceExecution
    {
        $this->targets->assertTechnician($companyId, $input->technicianId);
        $this->assertTicket($companyId, $input->supportTicketId);

        [$target, $type, $checklistItems] = $this->resolveSource($companyId, $input, $actorId);

        $execution = MaintenanceExecution::schedule(
            companyId: $companyId,
            planId: $input->planId,
            assetId: $target->assetId,
            locationId: $target->locationId,
            supportTicketId: $input->supportTicketId,
            targetType: $target->type,
            targetLabel: $target->label,
            type: $type,
            scheduledAt: $input->scheduledAt,
            technicianId: $input->technicianId,
            createdBy: $actorId,
            checklistItems: $checklistItems,
        );

        return $this->transactions->run(function () use ($execution, $actorId): MaintenanceExecution {
            $saved = $this->executions->save($execution);

            $this->audit->log(
                event: 'maintenance_execution.scheduled',
                description: "Se programó el mantenimiento {$saved->targetLabel} para {$saved->scheduledAt->format('d/m/Y H:i')}.",
                subjectType: MaintenanceExecutionModel::class,
                subjectId: $saved->id,
                newValues: $saved->auditValues(),
                metadata: ['created_by' => $actorId],
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }

    /**
     * @return array{0: MaintenanceResolvedTarget, 1: MaintenanceExecutionType, 2: array<int, MaintenanceExecutionChecklistItem>}
     */
    private function resolveSource(int $companyId, MaintenanceExecutionInput $input, int $actorId): array
    {
        if ($input->planId === null) {
            if ($input->type !== MaintenanceExecutionType::Corrective) {
                throw DomainException::because('Un mantenimiento sin plan solo puede ser correctivo.');
            }

            return [
                $this->targets->resolve($companyId, $input->targetType, $input->assetId, $input->locationId),
                MaintenanceExecutionType::Corrective,
                [],
            ];
        }

        $plan = $this->plans->find($input->planId, $companyId)
            ?? throw DomainException::because('El plan de mantenimiento no existe en esta empresa.');

        if ($plan->status !== MaintenancePlanStatus::Active) {
            throw DomainException::because('El plan está pausado y no se puede programar.');
        }

        $checklistItems = array_map(
            fn ($item) => new MaintenanceExecutionChecklistItem(
                id: null,
                planChecklistItemId: $item->id,
                label: $item->label,
                isRequired: $item->isRequired,
                sortOrder: $item->sortOrder,
            ),
            $plan->checklistItems,
        );

        return [
            new MaintenanceResolvedTarget($plan->targetType, $plan->assetId, $plan->locationId, $plan->targetLabel),
            MaintenanceExecutionType::Preventive,
            $checklistItems,
        ];
    }

    private function assertTicket(int $companyId, ?int $ticketId): void
    {
        if ($ticketId === null) {
            return;
        }

        if ($this->tickets->find($ticketId, $companyId) === null) {
            throw DomainException::because('El ticket vinculado no existe en esta empresa.');
        }
    }
}
