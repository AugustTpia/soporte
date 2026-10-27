<?php

use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs(createTestUser(createTestCompany()));

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = createTestUser(createTestCompany());

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('jobTitle', 'Coordinador de soporte')
        ->set('phone', '5512345678')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->job_title)->toEqual('Coordinador de soporte');
    expect($user->phone)->toEqual('5512345678');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = createTestUser(createTestCompany());

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile page requires an active tenant', function () {
    $user = createTestUser(createTestCompany(['status' => 'inactive']));

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});
