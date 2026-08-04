<?php

use App\Domain\Employee\Models\Assignment;
use App\Domain\Employee\Models\Employment;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Models\User;

it('lets an employee submit a time off request for themselves', function () {
    $employment = Employment::factory()->create();
    $user = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $policy = TimeOffPolicy::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/time-off/requests', [
        'employment_id' => $employment->id,
        'policy_id' => $policy->id,
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeek()->addDay()->toDateString(),
        'hours_requested' => 16,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'pending');
});

it('blocks an employee from requesting time off on behalf of someone else', function () {
    $employment = Employment::factory()->create();
    $otherEmployment = Employment::factory()->create();
    $user = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $policy = TimeOffPolicy::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/time-off/requests', [
        'employment_id' => $otherEmployment->id,
        'policy_id' => $policy->id,
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
        'hours_requested' => 8,
    ]);

    $response->assertStatus(403);
});

it('lets the current manager approve a direct report\'s request and deducts the ledger balance', function () {
    $managerEmployment = Employment::factory()->create();
    $manager = User::factory()->create(['role' => 'people_manager', 'employment_id' => $managerEmployment->id]);

    $employment = Employment::factory()->create();
    Assignment::factory()->for($employment)->create(['is_current' => true, 'manager_employment_id' => $managerEmployment->id]);

    $policy = TimeOffPolicy::factory()->create();
    $timeOffRequest = \App\Domain\TimeOff\Models\TimeOffRequest::create([
        'employment_id' => $employment->id,
        'policy_id' => $policy->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'hours_requested' => 8,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($manager);
    $response = $this->postJson("/api/v1/time-off/requests/{$timeOffRequest->id}/approve");

    $response->assertOk();
    $response->assertJsonPath('data.status', 'approved');

    $balance = \App\Domain\TimeOff\Models\TimeOffBalance::where('employment_id', $employment->id)->first();
    expect((float) $balance->balance_hours)->toBe(-8.0);
});

it('blocks a manager who does not manage the requester from deciding the request', function () {
    $unrelatedManager = User::factory()->create(['role' => 'people_manager', 'employment_id' => Employment::factory()->create()->id]);

    $employment = Employment::factory()->create();
    Assignment::factory()->for($employment)->create(['is_current' => true, 'manager_employment_id' => null]);

    $policy = TimeOffPolicy::factory()->create();
    $timeOffRequest = \App\Domain\TimeOff\Models\TimeOffRequest::create([
        'employment_id' => $employment->id,
        'policy_id' => $policy->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'hours_requested' => 8,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($unrelatedManager);
    $response = $this->postJson("/api/v1/time-off/requests/{$timeOffRequest->id}/approve");

    $response->assertStatus(403);
});
