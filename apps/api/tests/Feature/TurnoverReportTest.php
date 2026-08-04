<?php

use App\Domain\Employee\Services\TerminationService;
use App\Domain\Employee\Models\Employment;
use App\Models\User;

it('reflects a termination in the turnover summary', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employment = Employment::factory()->create(['employment_status' => 'active']);
    app(TerminationService::class)->terminate($employment, now()->toDateString(), 'Restructuring');

    $response = $this->getJson('/api/v1/reports/turnover');

    $response->assertOk();
    expect($response->json('data.termination_count'))->toBeGreaterThanOrEqual(1);
    expect(collect($response->json('data.terminations_by_reason'))->pluck('reason'))->toContain('Restructuring');
});

it('blocks non-back-office roles from viewing the turnover report', function () {
    $employment = Employment::factory()->create();
    $user = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $this->actingAs($user);

    $this->getJson('/api/v1/reports/turnover')->assertStatus(403);
});
