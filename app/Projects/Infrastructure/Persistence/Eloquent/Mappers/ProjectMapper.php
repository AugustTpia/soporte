<?php

namespace App\Projects\Infrastructure\Persistence\Eloquent\Mappers;

use App\Projects\Domain\Entities\Project;
use App\Projects\Domain\Enums\ProjectStatus;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class ProjectMapper
{
    public function toDomain(ProjectModel $model): Project
    {
        return new Project(
            id: $model->id,
            companyId: $model->company_id,
            name: $model->name,
            description: $model->description,
            status: ProjectStatus::from($model->status),
            managerId: (int) $model->manager_id,
            startDate: $this->toDate($model->start_date),
            dueDate: $this->toDate($model->due_date),
            createdBy: $model->created_by,
            managerName: $model->relationLoaded('manager') ? $model->manager?->name : null,
            createdAt: $this->toDate($model->created_at),
        );
    }

    public function fillModel(Project $project, ProjectModel $model): ProjectModel
    {
        $model->company_id = $project->companyId;
        $model->name = $project->name;
        $model->description = $project->description;
        $model->status = $project->status->value;
        $model->manager_id = $project->managerId;
        $model->start_date = $this->toCarbon($project->startDate);
        $model->due_date = $this->toCarbon($project->dueDate);
        $model->created_by = $project->createdBy;

        return $model;
    }

    private function toCarbon(?DateTimeImmutable $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::createFromInterface($value);
    }

    private function toDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
