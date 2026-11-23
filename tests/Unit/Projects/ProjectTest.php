<?php

use App\Projects\Domain\Entities\Project;
use App\Projects\Domain\Enums\ProjectStatus;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

test('project can be created with valid data', function () {
    $project = Project::create(
        companyId: 1,
        name: 'Proyecto de prueba',
        description: 'Descripción.',
        status: ProjectStatus::Planned,
        managerId: 2,
        startDate: null,
        dueDate: null,
        createdBy: 1,
    );

    expect($project->name)->toBe('Proyecto de prueba')
        ->and($project->status)->toBe(ProjectStatus::Planned)
        ->and($project->managerId)->toBe(2);
});

test('project guards invalid name and dates', function () {
    $this->expectException(DomainException::class);

    Project::create(
        companyId: 1,
        name: '   ',
        description: null,
        status: ProjectStatus::Planned,
        managerId: 2,
        startDate: null,
        dueDate: null,
        createdBy: 1,
    );
});

test('project status transitions are validated', function () {
    $project = Project::create(
        companyId: 1,
        name: 'Proyecto',
        description: null,
        status: ProjectStatus::Planned,
        managerId: 2,
        startDate: null,
        dueDate: null,
        createdBy: 1,
    );

    $project->changeStatus(ProjectStatus::Active);
    expect($project->status)->toBe(ProjectStatus::Active);

    $project->changeStatus(ProjectStatus::Completed);
    expect($project->status)->toBe(ProjectStatus::Completed);

    $this->expectException(DomainException::class);
    $project->changeStatus(ProjectStatus::Active);
});

test('project due date cannot be before start date', function () {
    $this->expectException(DomainException::class);

    Project::create(
        companyId: 1,
        name: 'Proyecto',
        description: null,
        status: ProjectStatus::Planned,
        managerId: 2,
        startDate: new DateTimeImmutable('2026-12-01'),
        dueDate: new DateTimeImmutable('2026-11-01'),
        createdBy: 1,
    );
});
