<?php

// EventFlow is invite-only: self-registration would let anyone mint an internal
// account (the user_type column defaults to 'internal'), so the routes are gone
// entirely rather than merely gated. See RegisteredUserController's deletion.

test('the registration form is gone', function () {
    $this->get('/register')->assertNotFound();
});

test('registering a new account is gone', function () {
    $this->post('/register', [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
});
