<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

test('enabling two-factor authentication generates a pending secret', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('two-factor.enable'));

    $response->assertRedirect(route('two-factor.show'));

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->hasTwoFactorEnabled())->toBeFalse();
});

test('the settings page shows a qr code while a secret is pending confirmation', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'));

    $response = $this->actingAs($user)->get(route('two-factor.show'));

    $response->assertInertia(fn ($page) => $page
        ->component('Settings/TwoFactorAuthentication')
        ->where('enabled', false)
        ->where('pending', true)
        ->has('qrCodeSvg')
    );
});

test('confirming with a valid code enables two-factor authentication', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'));
    $user->refresh();

    $validCode = (new Google2FA)->getCurrentOtp($user->two_factor_secret);

    $response = $this->actingAs($user)->post(route('two-factor.confirm'), ['code' => $validCode]);

    $response->assertRedirect(route('two-factor.show'));
    expect($user->refresh()->hasTwoFactorEnabled())->toBeTrue();
});

test('confirming with an invalid code fails', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'));

    $response = $this->actingAs($user)->post(route('two-factor.confirm'), ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    expect($user->refresh()->hasTwoFactorEnabled())->toBeFalse();
});

test('disabling requires the correct current password', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'));
    $user->refresh();
    $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => (new Google2FA)->getCurrentOtp($user->two_factor_secret),
    ]);

    $this->actingAs($user)->delete(route('two-factor.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');
    expect($user->refresh()->hasTwoFactorEnabled())->toBeTrue();

    $this->actingAs($user)->delete(route('two-factor.destroy'), ['password' => 'password'])
        ->assertRedirect(route('two-factor.show'));
    expect($user->refresh()->hasTwoFactorEnabled())->toBeFalse();
});
