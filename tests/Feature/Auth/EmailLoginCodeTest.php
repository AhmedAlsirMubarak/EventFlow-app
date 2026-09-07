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

test('requesting a code for an internal account fails', function () {
    $user = User::factory()->create();

    $this->post('/login/code', ['email' => $user->email])
        ->assertSessionHasErrors('email');
});

test('requesting a code for an unknown email fails', function () {
    $this->post('/login/code', ['email' => 'nobody@example.com'])
        ->assertSessionHasErrors('email');
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
