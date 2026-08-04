<?php

use App\Domain\ATS\Models\JobRequisition;
use App\Domain\Employee\Models\Employment;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Domain\TimeOff\Models\TimeOffRequest;
use App\Models\User;

it('reports accurate headcount, open requisitions, and pending time off counts', function () {
    $this->actingAs(User::factory()->admin()->create());

    Employment::factory()->count(3)->create(['employment_status' => 'active']);
    Employment::factory()->create(['employment_status' => 'terminated']);

    JobRequisition::factory()->create(['status' => 'open']);
    JobRequisition::factory()->create(['status' => 'closed']);

    $employment = Employment::factory()->create();
    $policy = TimeOffPolicy::factory()->create();
    TimeOffRequest::create([
        'employment_id' => $employment->id, 'policy_id' => $policy->id,
        'start_date' => now(), 'end_date' => now(), 'hours_requested' => 8,
        'status' => 'pending', 'requested_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/dashboard');

    $response->assertOk();
    expect($response->json('data.headcount'))->toBe(4); // 3 active + the one with the time-off request
    expect($response->json('data.open_requisitions'))->toBe(1);
    expect($response->json('data.pending_time_off_requests'))->toBe(1);
});
