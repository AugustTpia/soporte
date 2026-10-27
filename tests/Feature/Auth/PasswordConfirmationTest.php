<?php

test('confirm password screen can be rendered', function () {
    $user = createTestUser(createTestCompany());

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();
});
