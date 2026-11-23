<?php

namespace App\Projects\Infrastructure\Persistence\Eloquent\Mappers;

use App\Projects\Domain\Entities\ProjectTask;
use App\Projects\Domain\Enums\ProjectTaskPriority;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectTaskModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class ProjectTaskMapper
{
    public function toDomain(ProjectTaskModel $model): ProjectTask
    {
        return new ProjectTask(
            id: $model->id,
            projectId: $model->project_id,
            title: $model->title,
            description: $model->description,
            status: ProjectTaskStatus::from($model->status),
            priority: ProjectTaskPriority::from($model->priority),
            assignedUserId: $model->assigned_user_id,
            dueDate: $this->toDate($model->due_date),
            ticketId: $model->ticket_id,
            sortOrder: (int) $model->sort_order,
            createdBy: $model->created_by,
            assignedUserName: $model->relationLoaded('assignee') ? $model->assignee?->name : null,
            ticketLabel: ($model->relationLoaded('ticket') && $model->ticket !== null) ? $model->ticket->folio : null,
            createdAt: $this->toDate($model->created_at),
        );
    }

    public function fillModel(ProjectTask $task, ProjectTaskModel $model): ProjectTaskModel
    {
        $model->project_id = $task->projectId;
        $model->title = $task->title;
        $model->description = $task->description;
        $model->status = $task->status->value;
        $model->priority = $task->priority->value;
        $model->assigned_user_id = $task->assignedUserId;
        $model->due_date = $this->toCarbon($task->dueDate);
        $model->ticket_id = $task->ticketId;
        $model->sort_order = $task->sortOrder;
        $model->created_by = $task->createdBy;

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
