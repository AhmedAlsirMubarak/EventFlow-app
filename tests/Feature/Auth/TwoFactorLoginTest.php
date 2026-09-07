<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

test('a user without two-factor enabled logs straight in', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('a user with two-factor enabled is challenged instead of logged in', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'));
    $user->refresh();
    $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret),
    ]);
    $this->post('/logout');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('two-factor.login'));
});

test('the challenge screen redirects to login without a pending session', function () {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
});

test('a correct totp code completes login', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'));
    $user->refresh();
    $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret),
    ]);
    $this->post('/logout');
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $response = $this->post(route('two-factor.login.store'), [
        'code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret),
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('an incorrect totp code does not complete login', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'));
    $user->refresh();
    $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret),
    ]);
    $this->post('/logout');
    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $response = $this->post(route('two-factor.login.store'), ['code' => '000000']);

    $this->assertGuest();
    $response->assertSessionHasErrors('code');
});

test('external accounts are never asked for a second factor even if a secret exists', function () {
    $external = User::factory()->external()->create();

    $response = $this->post('/login', [
        'email' => $external->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($external);
    $response->assertRedirect(route('portal.dashboard', absolute: false));
});
