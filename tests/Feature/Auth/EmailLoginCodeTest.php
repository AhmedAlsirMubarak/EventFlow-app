<?php

use App\Models\LoginCode;
use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('the request-code screen can be rendered', function () {
    $this->get('/login/code')->assertOk();
});

test('an external user can request a sign-in code', function () {
    Notification::fake();

    $user = User::factory()->external()->create();

    $response = $this->post('/login/code', ['email' => $user->email]);

    $response->assertRedirect(route('login.code.verify', ['email' => $user->email]));
    $this->assertGuest();

    expect(LoginCode::where('user_id', $user->id)->count())->toBe(1);
    Notification::assertSentTo($user, LoginCodeNotification::class);
});

test('requesting a code for an internal account responds identically to a real one without sending anything', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/login/code', ['email' => $user->email]);

    // Same redirect and status as a genuine stakeholder request — a distinct response here
    // would let an attacker use this form to discover which emails have accounts.
    $response->assertRedirect(route('login.code.verify', ['email' => $user->email]));
    $response->assertSessionHas('status');
    $response->assertSessionDoesntHaveErrors();

    expect(LoginCode::where('user_id', $user->id)->count())->toBe(0);
    Notification::assertNothingSent();
});

test('requesting a code for an unknown email responds identically to a real one without sending anything', function () {
    Notification::fake();

    $response = $this->post('/login/code', ['email' => 'nobody@example.com']);

    $response->assertRedirect(route('login.code.verify', ['email' => 'nobody@example.com']));
    $response->assertSessionHas('status');
    $response->assertSessionDoesntHaveErrors();

    expect(LoginCode::count())->toBe(0);
    Notification::assertNothingSent();
});

test('requesting a new code invalidates the previous unconsumed code', function () {
    Notification::fake();

    $user = User::factory()->external()->create();

    $this->post('/login/code', ['email' => $user->email]);
    $firstCodeId = LoginCode::where('user_id', $user->id)->firstOrFail()->id;

    $this->post('/login/code', ['email' => $user->email]);

    expect(LoginCode::find($firstCodeId))->toBeNull();
    expect(LoginCode::where('user_id', $user->id)->count())->toBe(1);
});

test('an external user can sign in with a valid code', function () {
    $user = User::factory()->external()->create();
    $loginCode = LoginCode::factory()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('654321'),
    ]);

    $response = $this->post('/login/code/verify', [
        'email' => $user->email,
        'code' => '654321',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('portal.dashboard', absolute: false));
    expect($loginCode->refresh()->consumed_at)->not->toBeNull();
});

test('an incorrect code is rejected', function () {
    $user = User::factory()->external()->create();
    LoginCode::factory()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('654321'),
    ]);

    $this->post('/login/code/verify', [
        'email' => $user->email,
        'code' => '000000',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('a code is locked out after five wrong attempts even from a different IP', function () {
    // The attempts counter lives on the code row, not the per-IP RateLimiter, so it still
    // caps guesses against an attacker who rotates IPs to dodge the request-level throttle.
    $user = User::factory()->external()->create();
    $loginCode = LoginCode::factory()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('654321'),
        'attempts' => 5,
    ]);

    $response = $this->post('/login/code/verify', [
        'email' => $user->email,
        'code' => '654321',
    ]);

    $response->assertSessionHasErrors('code');
    $this->assertGuest();
    expect($loginCode->refresh()->consumed_at)->not->toBeNull();
});

test('an expired code is rejected', function () {
    $user = User::factory()->external()->create();
    LoginCode::factory()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('654321'),
        'expires_at' => now()->subMinute(),
    ]);

    $this->post('/login/code/verify', [
        'email' => $user->email,
        'code' => '654321',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('a code cannot be reused once consumed', function () {
    $user = User::factory()->external()->create();
    LoginCode::factory()->create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('654321'),
        'consumed_at' => now(),
    ]);

    $this->post('/login/code/verify', [
        'email' => $user->email,
        'code' => '654321',
    ])->assertSessionHasErrors('code');

    $this->assertGuest();
});
