<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('a profile photo can be uploaded', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $avatar = UploadedFile::fake()->image('avatar.png', 200, 200)->size(100);

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $avatar,
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->avatar_path)->not->toBeNull();
    expect($user->avatar_url)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('a non-image file is rejected as a profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('not-a-photo.pdf', 100, 'application/pdf');

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $file,
    ])->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar_path)->toBeNull();
});

test('uploading a new profile photo replaces and deletes the old file', function () {
    Storage::fake('public');
    $user = User::factory()->create(['avatar_path' => 'avatars/old.png']);
    Storage::disk('public')->put('avatars/old.png', 'fake-content');

    $newAvatar = UploadedFile::fake()->image('new-avatar.png');

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $newAvatar,
    ]);

    $user->refresh();
    Storage::disk('public')->assertMissing('avatars/old.png');
    Storage::disk('public')->assertExists($user->avatar_path);
    expect($user->avatar_path)->not->toBe('avatars/old.png');
});

test('the profile photo can be removed without uploading a new one', function () {
    Storage::fake('public');
    $user = User::factory()->create(['avatar_path' => 'avatars/current.png']);
    Storage::disk('public')->put('avatars/current.png', 'fake-content');

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'remove_avatar' => true,
    ]);

    $user->refresh();
    expect($user->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/current.png');
});

test('the profile photo is deleted when the account is deleted', function () {
    Storage::fake('public');
    $user = User::factory()->create(['avatar_path' => 'avatars/to-delete.png']);
    Storage::disk('public')->put('avatars/to-delete.png', 'fake-content');

    $this->actingAs($user)->delete('/profile', [
        'password' => 'password',
    ]);

    Storage::disk('public')->assertMissing('avatars/to-delete.png');
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
