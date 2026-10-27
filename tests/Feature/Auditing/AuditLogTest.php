<?php

use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Auditing\Presentation\Livewire\AuditIndex;
use App\Identity\Domain\Enums\RoleName;
use App\Shared\Application\Contracts\AuditLogger;
use Livewire\Livewire;

test('audit logs sanitize sensitive values', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    app(AuditLogger::class)->log(
        event: 'test.sensitive',
        description: 'Prueba de sanitización',
        oldValues: [
            'password' => 'super-secret',
            'safe' => 'visible',
            'nested' => [
                'token' => 'abc123',
                'value' => 'allowed',
            ],
        ],
        metadata: [
            'cookie' => 'session-cookie',
            'environment' => 'testing',
        ],
        companyId: $company->id,
    );

    $log = AuditLogModel::query()->where('event', 'test.sensitive')->firstOrFail();

    expect($log->old_values)->toBe([
        'safe' => 'visible',
        'nested' => ['value' => 'allowed'],
    ]);
    expect($log->metadata)->toBe(['environment' => 'testing']);
    expect(json_encode($log->old_values))->not->toContain('super-secret');
    expect(json_encode($log->metadata))->not->toContain('session-cookie');
});

test('company administrators only see audit entries from their company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    app(AuditLogger::class)->log(
        event: 'test.company',
        description: 'Evento de mi empresa',
        companyId: $company->id,
    );

    app(AuditLogger::class)->log(
        event: 'test.other',
        description: 'Evento de otra empresa',
        companyId: $otherCompany->id,
    );

    Livewire::test(AuditIndex::class)
        ->assertSee('Evento de mi empresa')
        ->assertDontSee('Evento de otra empresa');
});

test('super admins can see the global audit log', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin);

    app(AuditLogger::class)->log(
        event: 'test.company',
        description: 'Evento empresa A',
        companyId: $company->id,
    );

    app(AuditLogger::class)->log(
        event: 'test.other',
        description: 'Evento empresa B',
        companyId: $otherCompany->id,
    );

    Livewire::test(AuditIndex::class)
        ->assertSee('Evento empresa A')
        ->assertSee('Evento empresa B');
});
