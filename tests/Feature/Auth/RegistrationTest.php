<?php

test('registration screen is not available for guests', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('guests can not register users directly', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile_number' => '+15555555555',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertNotFound();
});
