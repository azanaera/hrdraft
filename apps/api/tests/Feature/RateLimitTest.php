<?php

use App\Models\User;

it('locks out login after 5 failed attempts per minute', function () {
    User::factory()->create(['email' => 'throttle-me@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', ['email' => 'throttle-me@example.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    $response = $this->postJson('/api/v1/auth/login', ['email' => 'throttle-me@example.com', 'password' => 'wrong']);
    $response->assertStatus(429);
});
