<?php

use App\Companies\Application\DTOs\CompanyInput;
use App\Companies\Application\UseCases\UpdateCompany;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Presentation\Livewire\Companies\CompanyForm;
use Livewire\Livewire;

test('updating a company preserves settings that were not provided', function () {
    $company = createTestCompany([
        'settings' => [
            'custom' => 'conservar',
            'features' => ['existing' => true],
        ],
    ]);

    app(UpdateCompany::class)->execute($company->id, new CompanyInput(
        name: 'Empresa Actualizada',
        timezone: 'America/Mexico_City',
        locale: 'es_MX',
    ));

    $settings = $company->refresh()->settings;

    expect($settings['custom'])->toBe('conservar')
        ->and($settings['features']['existing'])->toBeTrue();
});

test('super admins can enable the bus ticket cancellation feature', function () {
    $company = createTestCompany([
        'settings' => [
            'custom' => 'conservar',
            'features' => ['existing' => true],
        ],
    ]);
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin);

    Livewire::test(CompanyForm::class, ['company' => $company])
        ->set('busTicketCancellations', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('companies.index'));

    $company->refresh();

    expect($company->settings['custom'])->toBe('conservar')
        ->and($company->settings['features']['existing'])->toBeTrue()
        ->and($company->settings['features'][CompanyFeature::BusTicketCancellations->value])->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'company.updated',
        'subject_id' => $company->id,
    ]);
});
