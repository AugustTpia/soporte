<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Application\DTOs\MaintenancePlanInput;
use App\Maintenance\Application\Services\MaintenanceTargetResolver;
use App\Maintenance\Domain\Entities\MaintenancePlan;
use App\Maintenance\Domain\Entities\MaintenancePlanChecklistItem;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UpdateMaintenancePlan
{
    public function __construct(
        private MaintenancePlanRepository $plans,
        private MaintenanceTargetResolver $targets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $planId, MaintenancePlanInput $input): MaintenancePlan
    {
        $plan = $this->plans->find($planId, $companyId)
            ?? throw DomainException::because('El plan de mantenimiento no existe en esta empresa.');

        $target = $this->targets->resolve(
            companyId: $companyId,
            targetType: $input->targetType,
            assetId: $input->assetId,
            locationId: $input->locationId,
        );

        $this->targets->assertTechnician($companyId, $input->assignedUserId);
        $oldValues = $plan->auditValues();

        $plan->updateDetails(
            targetType: $target->type,
            assetId: $target->assetId,
            locationId: $target->locationId,
            targetLabel: $target->label,
            title: $input->title,
            description: $input->description,
            intervalValue: $input->intervalValue,
            intervalUnit: $input->intervalUnit,
            nextDueAt: $input->nextDueAt,
            assignedUserId: $input->assignedUserId,
            checklistItems: $this->checklist($input),
        );

        return $this->transactions->run(function () use ($plan, $oldValues): MaintenancePlan {
            $saved = $this->plans->save($plan);

            $this->audit->log(
                event: 'maintenance_plan.updated',
                description: "Se actualizó el plan de mantenimiento {$saved->title}.",
                subjectType: MaintenancePlanModel::class,
                subjectId: $saved->id,
                oldValues: $oldValues,
                newValues: $saved->auditValues(),
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }

    /**
     * @return array<int, MaintenancePlanChecklistItem>
     */
    private function checklist(MaintenancePlanInput $input): array
    {
        return array_map(
            fn ($item, int $index) => new MaintenancePlanChecklistItem(
                id: null,
                label: $item->label,
                isRequired: $item->isRequired,
                sortOrder: $index,
            ),
            $input->checklistItems,
            array_keys($input->checklistItems),
        );
    }
}
