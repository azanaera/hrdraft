<?php

use App\Models\User;

it('throttles the general authenticated API after 300 requests per minute, per user', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    for ($i = 0; $i < 300; $i++) {
        $this->getJson('/api/v1/auth/me')->assertOk();
    }

    $this->getJson('/api/v1/auth/me')->assertStatus(429);
});

it('locks out login after 5 failed attempts per minute', function () {
    User::factory()->create(['email' => 'throttle-me@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', ['email' => 'throttle-me@example.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    $response = $this->postJson('/api/v1/auth/login', ['email' => 'throttle-me@example.com', 'password' => 'wrong']);
    $response->assertStatus(429);
});
