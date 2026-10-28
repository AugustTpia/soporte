<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Domain\Entities\MaintenancePlan;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class ChangeMaintenancePlanStatus
{
    public function __construct(
        private MaintenancePlanRepository $plans,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, int $planId, MaintenancePlanStatus $status): MaintenancePlan
    {
        $plan = $this->plans->find($planId, $companyId)
            ?? throw DomainException::because('El plan de mantenimiento no existe en esta empresa.');

        $old = $plan->status;
        $plan->changeStatus($status);

        return $this->transactions->run(function () use ($plan, $old, $status): MaintenancePlan {
            $saved = $this->plans->save($plan);

            $this->audit->log(
                event: 'maintenance_plan.status_changed',
                description: "El plan {$saved->title} cambió de {$old->label()} a {$status->label()}.",
                subjectType: MaintenancePlanModel::class,
                subjectId: $saved->id,
                oldValues: ['status' => $old->value],
                newValues: ['status' => $status->value],
                companyId: $saved->companyId,
            );

            return $saved;
        });
    }
}
