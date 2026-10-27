<?php

use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Credentials\Presentation\Livewire\CredentialForm;
use App\Identity\Domain\Enums\RoleName;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function createCredentialContext(?RoleName $role = RoleName::CompanyAdmin): array
{
    $company = createTestCompany();
    $user = createTestUser($company, [], $role);

    $account = ServiceAccountModel::factory()->forCompany($company)->create([
        'service' => 'nextcloud',
        'username' => 'juan.perez',
        'instance' => 'cloud.example.test',
    ]);

    $credential = CredentialModel::query()->create([
        'company_id' => $company->id,
        'service_account_id' => $account->id,
        'label' => 'Contraseña principal',
        'kind' => 'password',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    return [$company, $user, $account, $credential];
}

/**
 * @return array<string, string>
 */
function secretPayload(string $secret = 'S3cret-value'): array
{
    return [
        'current_password' => 'password',
        'secret' => $secret,
        'secret_confirmation' => $secret,
    ];
}

test('company administrators can create credential metadata without a secret', function () {
    [$company, $user, $account] = createCredentialContext();

    $this->actingAs($user);

    $component = Livewire::test(CredentialForm::class)
        ->set('serviceAccountId', (string) $account->id)
        ->set('label', '  Token de respaldo  ')
        ->set('kind', 'token')
        ->call('save')
        ->assertHasNoErrors();

    $credential = CredentialModel::query()->where('label', 'Token de respaldo')->firstOrFail();

    expect($credential->company_id)->toBe($company->id)
        ->and($credential->service_account_id)->toBe($account->id)
        ->and($credential->kind)->toBe('token')
        ->and($credential->status)->toBe('active');

    $component->assertRedirect(route('credentials.edit', $credential->id));

    $this->assertDatabaseCount('credential_secrets', 0);
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'credential.created',
        'company_id' => $company->id,
    ]);
});

test('a secret is encrypted and revealed only after reauthentication', function () {
    [$company, $user, , $credential] = createCredentialContext();

    $this->actingAs($user)
        ->post(route('credentials.secret.store', $credential), secretPayload())
        ->assertSessionHasNoErrors();

    $stored = DB::table('credential_secrets')
        ->where('credential_reference_id', $credential->id)
        ->firstOrFail();

    expect($stored->ciphertext)->not->toContain('S3cret-value')
        ->and($stored->key_id)->not->toBe('')
        ->and($stored->version)->toBe(1);

    $credential->refresh();

    expect($credential->last_rotated_at)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'credential.secret_created',
        'subject_id' => $credential->id,
        'company_id' => $company->id,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('credentials.reveal', $credential), [
            'current_password' => 'password',
        ])
        ->assertOk()
        ->assertJson(['secret' => 'S3cret-value']);

    expect($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'credential.revealed',
        'subject_id' => $credential->id,
        'company_id' => $company->id,
    ]);

    $audit = AuditLogModel::query()->where('event', 'credential.revealed')->firstOrFail();
    expect(json_encode($audit->toArray(), JSON_THROW_ON_ERROR))->not->toContain('S3cret-value');

    $this->actingAs($user)
        ->get(route('credentials.edit', $credential))
        ->assertOk()
        ->assertSee('name="secret"', false)
        ->assertSee('name="secret_confirmation"', false)
        ->assertDontSee('S3cret-value');

    $this->actingAs($user)
        ->get(route('credentials.index'))
        ->assertOk()
        ->assertDontSee('S3cret-value');
});

test('rotating a secret increments its version and replaces the old value', function () {
    [, $user, , $credential] = createCredentialContext();

    $this->actingAs($user)
        ->post(route('credentials.secret.store', $credential), secretPayload('first-secret'))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('credentials.secret.store', $credential), secretPayload('second-secret'))
        ->assertSessionHasNoErrors();

    $stored = DB::table('credential_secrets')
        ->where('credential_reference_id', $credential->id)
        ->firstOrFail();

    expect($stored->version)->toBe(2);

    $this->actingAs($user)
        ->postJson(route('credentials.reveal', $credential), ['current_password' => 'password'])
        ->assertOk()
        ->assertJson(['secret' => 'second-secret']);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'credential.secret_rotated',
        'subject_id' => $credential->id,
    ]);
});

test('a wrong current password denies setting and revealing secrets', function () {
    [$company, $user, , $credential] = createCredentialContext();

    $this->actingAs($user)
        ->post(route('credentials.secret.store', $credential), [
            'current_password' => 'wrong-password',
            'secret' => 'S3cret-value',
            'secret_confirmation' => 'S3cret-value',
        ])
        ->assertSessionHasErrors('current_password');

    $this->assertDatabaseCount('credential_secrets', 0);
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'credential.secret_change_denied',
        'subject_id' => $credential->id,
        'company_id' => $company->id,
    ]);

    $this->actingAs($user)
        ->post(route('credentials.secret.store', $credential), secretPayload())
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->postJson(route('credentials.reveal', $credential), ['current_password' => 'wrong-password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('current_password');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'credential.reveal_denied',
        'subject_id' => $credential->id,
        'company_id' => $company->id,
    ]);
});

test('revoked credentials can not reveal their secret', function () {
    [, $user, , $credential] = createCredentialContext();

    $this->actingAs($user)
        ->post(route('credentials.secret.store', $credential), secretPayload())
        ->assertSessionHasNoErrors();

    $credential->update(['status' => 'revoked']);

    $this->actingAs($user)
        ->postJson(route('credentials.reveal', $credential), ['current_password' => 'password'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'No se puede revelar una credencial revocada.');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'credential.reveal_denied',
        'subject_id' => $credential->id,
    ]);
});

test('view only roles can see metadata but can not reveal secrets', function () {
    [, $auditor, , $credential] = createCredentialContext(RoleName::Auditor);

    $this->actingAs($auditor)
        ->get(route('credentials.edit', $credential))
        ->assertOk()
        ->assertSee('Contraseña principal')
        ->assertDontSee('Revelar secreto')
        ->assertDontSee('Guardar secreto');

    $this->actingAs($auditor)
        ->postJson(route('credentials.reveal', $credential), ['current_password' => 'password'])
        ->assertForbidden();
});

test('credentials are isolated by company', function () {
    [, , , $credential] = createCredentialContext();

    $otherCompany = createTestCompany();
    $otherAdmin = createTestUser($otherCompany, [], RoleName::CompanyAdmin);

    $this->actingAs($otherAdmin)
        ->get(route('credentials.edit', $credential))
        ->assertForbidden();

    $this->actingAs($otherAdmin)
        ->postJson(route('credentials.reveal', $credential), ['current_password' => 'password'])
        ->assertForbidden();
});

test('read only roles can not open the credential module form actions', function () {
    [, $readOnly, , $credential] = createCredentialContext(RoleName::ReadOnly);

    $this->actingAs($readOnly)
        ->get(route('credentials.edit', $credential))
        ->assertOk()
        ->assertDontSee('Guardar cambios');
});
