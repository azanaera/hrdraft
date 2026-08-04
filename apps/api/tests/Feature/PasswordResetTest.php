<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;

it('always responds the same way regardless of whether the email is registered', function () {
    $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'admin@example.com']);
    $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

    $known->assertOk();
    $unknown->assertOk();
    expect($known->json('message'))->toBe($unknown->json('message'));
});

it('resets the password with a valid token and revokes existing tokens', function () {
    $user = User::factory()->create(['email' => 'reset-me@example.com']);
    $user->createToken('device');
    expect($user->tokens()->count())->toBe(1);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ]);

    $response->assertOk();
    expect($user->tokens()->count())->toBe(0);

    // Matches how a real browser SPA request carries an Origin/Referer from
    // a stateful domain — see AuthTest for the full explanation.
    $login = $this->withHeader('Referer', 'http://localhost:5173')
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'new-secret-123']);
    $login->assertOk();
});

it('rejects an invalid reset token', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ]);

    $response->assertStatus(422);
});
