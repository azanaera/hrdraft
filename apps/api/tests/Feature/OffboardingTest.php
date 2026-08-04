<?php

use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Services\TerminationService;
use App\Domain\Offboarding\Models\OffboardingTemplate;
use App\Domain\TimeOff\Models\TimeOffBalance;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Models\User;

it('completes the offboarding workflow once all tasks are done', function () {
    $this->actingAs(User::factory()->admin()->create());

    $template = OffboardingTemplate::factory()->create();
    $template->tasks()->create(['title' => 'Return badge', 'task_type' => 'equipment_return', 'order' => 1, 'is_required' => true]);

    $employment = Employment::factory()->create(['employment_status' => 'active']);
    app(TerminationService::class)->terminate($employment, now()->toDateString(), 'Resignation');

    $workflow = $this->getJson("/api/v1/employees/{$employment->id}/offboarding")->json('data');
    expect($workflow['tasks'])->toHaveCount(1);

    $taskId = $workflow['tasks'][0]['id'];
    $this->postJson("/api/v1/offboarding/tasks/{$taskId}/complete")->assertOk();

    $reloaded = $this->getJson("/api/v1/employees/{$employment->id}/offboarding")->json('data');
    expect($reloaded['status'])->toBe('completed');
});

it('calculates a final payout figure from the unused time-off balance when offboarding starts', function () {
    $this->actingAs(User::factory()->admin()->create());

    OffboardingTemplate::factory()->create();

    $employment = Employment::factory()->create([
        'employment_status' => 'active',
        'employment_type' => 'hourly',
    ]);
    \App\Domain\Compensation\Models\CompensationRecord::create([
        'employment_id' => $employment->id, 'pay_type' => 'hourly', 'rate_amount' => 20,
        'pay_frequency' => 'biweekly', 'effective_date' => now()->subMonth()->toDateString(), 'reason' => 'new_hire',
    ]);
    $policy = TimeOffPolicy::factory()->create();
    TimeOffBalance::create(['employment_id' => $employment->id, 'policy_id' => $policy->id, 'balance_hours' => 10, 'as_of_date' => now()->toDateString()]);

    app(TerminationService::class)->terminate($employment, now()->toDateString(), 'Resignation');

    $event = $employment->person->events()->where('event_type', 'final_payout_calculated')->first();
    expect($event)->not->toBeNull();
    // PHP's json_encode can drop the trailing .0 on whole-number floats, so
    // the round-tripped payload value may decode as int or float — compare
    // numerically rather than relying on json_decode's inferred type.
    expect((float) $event->payload['total_amount'])->toBe(200.0); // 10 hrs * $20/hr
});
