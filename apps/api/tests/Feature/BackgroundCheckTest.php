<?php

use App\Domain\Employee\Models\Employment;
use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Domain\Onboarding\Services\OnboardingWorkflowService;
use App\Models\User;

it('runs both background_check and e_verify automatically when onboarding starts', function () {
    $this->actingAs(User::factory()->admin()->create());

    $template = OnboardingTemplate::factory()->create();
    $employment = Employment::factory()->create();

    app(OnboardingWorkflowService::class)->start($employment, $template);

    $response = $this->getJson("/api/v1/employees/{$employment->id}/background-checks");

    $response->assertOk();
    $types = collect($response->json('data'))->pluck('check_type')->all();
    expect($types)->toContain('background_check', 'e_verify');
    expect(collect($response->json('data'))->pluck('status')->unique()->all())->toBe(['clear']);
});
