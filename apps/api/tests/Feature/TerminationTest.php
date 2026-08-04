<?php

use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Services\TerminationService;
use App\Domain\Offboarding\Models\OffboardingTemplate;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('revokes the terminated employee\'s tokens and login access', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employment = Employment::factory()->create(['employment_status' => 'active']);
    $employeeUser = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $token = $employeeUser->createToken('device')->plainTextToken;

    expect($employeeUser->tokens()->count())->toBe(1);

    app(TerminationService::class)->terminate($employment, now()->toDateString(), 'Policy violation');

    expect($employeeUser->tokens()->count())->toBe(0);

    $response = $this->postJson('/api/v1/auth/mobile-login', [
        'email' => $employeeUser->email,
        'password' => 'password',
        'device_name' => 'retry',
    ]);
    $response->assertStatus(422);
});

it('blocks a terminated user\'s existing token via the active.employment middleware', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employment = Employment::factory()->create(['employment_status' => 'active']);
    $employeeUser = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);

    Sanctum::actingAs($employeeUser);
    $this->getJson('/api/v1/auth/me')->assertOk();

    $employment->update(['employment_status' => 'terminated']);

    $this->getJson('/api/v1/auth/me')->assertStatus(401);
});

it('starts an offboarding workflow and records a timeline event on termination', function () {
    $this->actingAs(User::factory()->admin()->create());

    OffboardingTemplate::factory()->create(['is_active' => true])->tasks()->create([
        'title' => 'Return badge', 'task_type' => 'equipment_return', 'order' => 1, 'is_required' => true,
    ]);

    $employment = Employment::factory()->create(['employment_status' => 'active']);

    app(TerminationService::class)->terminate($employment, now()->toDateString(), 'Layoff');

    expect($employment->fresh()->employment_status)->toBe('terminated');
    expect($employment->person->events()->where('event_type', 'terminated')->count())->toBe(1);

    $response = $this->getJson("/api/v1/employees/{$employment->id}/offboarding");
    $response->assertOk();
    $response->assertJsonPath('data.status', 'in_progress');
});
