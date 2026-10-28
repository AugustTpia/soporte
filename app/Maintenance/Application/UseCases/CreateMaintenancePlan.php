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

final readonly class CreateMaintenancePlan
{
    public function __construct(
        private MaintenancePlanRepository $plans,
        private MaintenanceTargetResolver $targets,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, MaintenancePlanInput $input, int $actorId): MaintenancePlan
    {
        $target = $this->targets->resolve(
            companyId: $companyId,
            targetType: $input->targetType,
            assetId: $input->assetId,
            locationId: $input->locationId,
        );

        $this->targets->assertTechnician($companyId, $input->assignedUserId);

        $plan = MaintenancePlan::create(
            companyId: $companyId,
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
            createdBy: $actorId,
            checklistItems: $this->checklist($input),
        );

        return $this->transactions->run(function () use ($plan): MaintenancePlan {
            $saved = $this->plans->save($plan);

            $this->audit->log(
                event: 'maintenance_plan.created',
                description: "Se creó el plan de mantenimiento {$saved->title}.",
                subjectType: MaintenancePlanModel::class,
                subjectId: $saved->id,
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
