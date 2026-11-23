<?php

use App\Assistant\GeminiAssistantService;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Companies\Presentation\Livewire\Companies\CompanyForm;
use Livewire\Livewire;

function disableAssistantFor(CompanyModel $company): void
{
    $settings = $company->settings ?? [];
    $settings['features']['assistant'] = false;
    $company->update(['settings' => $settings]);
    $company->refresh();
}

test('el asistente esta activado por defecto para una empresa sin el ajuste guardado', function () {
    $company = createTestCompany();

    $domain = app(CompanyRepository::class)->find($company->id);

    expect($domain?->assistantEnabled())->toBeTrue();
});

test('la pagina del asistente muestra el chat cuando esta activado', function () {
    $company = createTestCompany();
    $user = createTestUser($company);

    $this->actingAs($user)
        ->get(route('assistant.index'))
        ->assertOk()
        ->assertSee('Asistente de soporte')
        ->assertDontSee('no está activado para esta empresa');
});

test('la pagina del asistente muestra un mensaje amable cuando esta desactivado', function () {
    $company = createTestCompany();
    disableAssistantFor($company);
    $user = createTestUser($company);

    $this->actingAs($user)
        ->get(route('assistant.index'))
        ->assertOk()
        ->assertSee('no está activado para esta empresa')
        ->assertDontSee('data-assistant-chat');
});

test('el endpoint del chat responde cuando el asistente esta activado', function () {
    $company = createTestCompany();
    $user = createTestUser($company);

    $fake = new class extends GeminiAssistantService
    {
        public function ask(string $message, array $history = []): array
        {
            return ['reply' => 'Respuesta de prueba.', 'model' => 'test', 'error' => null];
        }
    };

    app()->instance(GeminiAssistantService::class, $fake);

    $this->actingAs($user)
        ->postJson(route('assistant.chat.send'), ['message' => 'Hola'])
        ->assertOk()
        ->assertJsonPath('reply', 'Respuesta de prueba.')
        ->assertJsonMissing(['disabled' => true]);
});

test('el endpoint del chat se bloquea con un mensaje amable cuando esta desactivado', function () {
    $company = createTestCompany();
    disableAssistantFor($company);
    $user = createTestUser($company);

    $this->actingAs($user)
        ->postJson(route('assistant.chat.send'), ['message' => 'Hola'])
        ->assertOk()
        ->assertJsonPath('disabled', true)
        ->assertJsonFragment(['reply' => 'El asistente IA no está activado para esta empresa. Pide al superadministrador que lo active en la ficha de la empresa.']);
});

test('el superadmin puede desactivar el asistente desde el formulario de empresa', function () {
    $company = createTestCompany();
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin);

    Livewire::test(CompanyForm::class, ['company' => $company])
        ->set('assistantEnabled', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('companies.index'));

    expect($company->refresh()->settings['features'][CompanyFeature::Assistant->value])->toBeFalse();
});

test('una empresa nueva se crea con el asistente activado por defecto', function () {
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin);

    Livewire::test(CompanyForm::class)
        ->set('name', 'Empresa Con Asistente')
        ->set('timezone', 'America/Mexico_City')
        ->set('locale', 'es_MX')
        ->call('save')
        ->assertHasNoErrors();

    $company = CompanyModel::query()->where('name', 'Empresa Con Asistente')->firstOrFail();

    expect($company->settings['features'][CompanyFeature::Assistant->value])->toBeTrue();
});
