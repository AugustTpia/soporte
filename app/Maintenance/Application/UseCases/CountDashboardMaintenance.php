<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;

final readonly class CountDashboardMaintenance
{
    public function __construct(
        private MaintenancePlanRepository $plans,
        private MaintenanceExecutionRepository $executions,
    ) {}

    /**
     * @return array{active_plans: int, overdue: int, upcoming: int, scheduled: int, in_progress: int, completed_month: int}
     */
    public function execute(int $companyId): array
    {
        $plans = $this->plans->counts($companyId);
        $executions = $this->executions->counts($companyId);

        return [
            'active_plans' => $plans['active'],
            'overdue' => $plans['overdue'],
            'upcoming' => $plans['upcoming'],
            'scheduled' => $executions['scheduled'],
            'in_progress' => $executions['in_progress'],
            'completed_month' => $executions['completed_month'],
        ];
    }
}
