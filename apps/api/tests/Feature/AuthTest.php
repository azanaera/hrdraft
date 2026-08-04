<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('logs a web user in via session auth', function () {
    User::factory()->create(['email' => 'test@example.com', 'password' => Hash::make('secret123')]);

    // A real browser SPA request carries an Origin/Referer from a stateful
    // domain, which is what makes Sanctum's EnsureFrontendRequestsAreStateful
    // middleware attach session handling to the request.
    $response = $this->withHeader('Referer', 'http://localhost:5173')->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.email', 'test@example.com');
});

it('rejects invalid web credentials', function () {
    User::factory()->create(['email' => 'test@example.com', 'password' => Hash::make('secret123')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

it('issues a bearer token for mobile login', function () {
    User::factory()->create(['email' => 'mobile@example.com', 'password' => Hash::make('secret123')]);

    $response = $this->postJson('/api/v1/auth/mobile-login', [
        'email' => 'mobile@example.com',
        'password' => 'secret123',
        'device_name' => 'iphone-15',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['data', 'token']);
});

it('rejects unauthenticated access to protected routes', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

it('logs out a web/session user without error', function () {
    // Regression test: web/cookie-session auth resolves to a Sanctum
    // TransientToken (no delete() method) — logout must not assume every
    // authenticated request has a real, deletable PersonalAccessToken.
    $user = User::factory()->create(['email' => 'weblogout@example.com', 'password' => Hash::make('secret123')]);

    $this->withHeader('Referer', 'http://localhost:5173')->postJson('/api/v1/auth/login', [
        'email' => 'weblogout@example.com',
        'password' => 'secret123',
    ])->assertOk();

    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertNoContent();
});

it('logs out a mobile/token user and revokes their token', function () {
    $user = User::factory()->create(['email' => 'mobilelogout@example.com', 'password' => Hash::make('secret123')]);
    $token = $user->createToken('device')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/auth/logout');

    $response->assertNoContent();
    expect($user->tokens()->count())->toBe(0);
});
