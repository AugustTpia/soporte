<?php

use App\Projects\Domain\Entities\ProjectTask;
use App\Projects\Domain\Enums\ProjectTaskPriority;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use App\Shared\Domain\Exceptions\DomainException;

test('project task can be created with valid data', function () {
    $task = ProjectTask::create(
        projectId: 1,
        title: 'Configurar servidor',
        description: 'Descripción.',
        status: ProjectTaskStatus::Pending,
        priority: ProjectTaskPriority::High,
        assignedUserId: 2,
        dueDate: null,
        ticketId: null,
        sortOrder: 0,
        createdBy: 1,
    );

    expect($task->title)->toBe('Configurar servidor')
        ->and($task->status)->toBe(ProjectTaskStatus::Pending)
        ->and($task->assignedUserId)->toBe(2);
});

test('project task guards invalid data', function () {
    $this->expectException(DomainException::class);

    ProjectTask::create(
        projectId: 1,
        title: '   ',
        description: null,
        status: ProjectTaskStatus::Pending,
        priority: ProjectTaskPriority::Low,
        assignedUserId: null,
        dueDate: null,
        ticketId: null,
        sortOrder: 0,
        createdBy: 1,
    );
});

test('project task status transitions are validated', function () {
    $task = ProjectTask::create(
        projectId: 1,
        title: 'Tarea',
        description: null,
        status: ProjectTaskStatus::Pending,
        priority: ProjectTaskPriority::Low,
        assignedUserId: null,
        dueDate: null,
        ticketId: null,
        sortOrder: 0,
        createdBy: 1,
    );

    $task->moveTo(ProjectTaskStatus::InProgress, 0);
    expect($task->status)->toBe(ProjectTaskStatus::InProgress);

    $task->moveTo(ProjectTaskStatus::Completed, 1);
    expect($task->status)->toBe(ProjectTaskStatus::Completed);

    $this->expectException(DomainException::class);
    $task->moveTo(ProjectTaskStatus::Blocked, 3);
});

test('project task can only be assigned a valid user', function () {
    $this->expectException(DomainException::class);

    $task = ProjectTask::create(
        projectId: 1,
        title: 'Tarea',
        description: null,
        status: ProjectTaskStatus::Pending,
        priority: ProjectTaskPriority::Low,
        assignedUserId: null,
        dueDate: null,
        ticketId: null,
        sortOrder: 0,
        createdBy: 1,
    );

    $task->assign(0);
});
