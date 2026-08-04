<?php

use App\Domain\Compensation\Services\CompensationService;
use App\Domain\Employee\Models\Assignment;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Location;
use App\Models\User;

it('rejects an hourly rate below the assignment location\'s minimum wage', function () {
    $this->actingAs(User::factory()->admin()->create());

    $location = Location::factory()->create(['minimum_wage' => 15.00]);
    $employment = Employment::factory()->create();
    Assignment::factory()->for($employment)->create(['location_id' => $location->id, 'is_current' => true]);

    app(CompensationService::class)->applyChange($employment, [
        'pay_type' => 'hourly', 'rate_amount' => 10.00, 'pay_frequency' => 'biweekly',
        'effective_date' => now()->toDateString(), 'reason' => 'new_hire',
    ]);
})->throws(\App\Domain\Compensation\Services\WageComplianceException::class);

it('returns a 422 with a clear message via the API when a rate is below minimum wage', function () {
    $this->actingAs(User::factory()->admin()->create());

    $location = Location::factory()->create(['minimum_wage' => 15.00]);
    $employment = Employment::factory()->create();
    Assignment::factory()->for($employment)->create(['location_id' => $location->id, 'is_current' => true]);

    $response = $this->postJson("/api/v1/employees/{$employment->id}/compensation", [
        'pay_type' => 'hourly', 'rate_amount' => 9.00, 'pay_frequency' => 'biweekly',
        'effective_date' => now()->toDateString(), 'reason' => 'new_hire',
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('minimum wage');
});

it('allows an hourly rate at or above minimum wage', function () {
    $this->actingAs(User::factory()->admin()->create());

    $location = Location::factory()->create(['minimum_wage' => 15.00]);
    $employment = Employment::factory()->create();
    Assignment::factory()->for($employment)->create(['location_id' => $location->id, 'is_current' => true]);

    $response = $this->postJson("/api/v1/employees/{$employment->id}/compensation", [
        'pay_type' => 'hourly', 'rate_amount' => 15.00, 'pay_frequency' => 'biweekly',
        'effective_date' => now()->toDateString(), 'reason' => 'new_hire',
    ]);

    $response->assertCreated();
});

it('does not validate minimum wage for salaried compensation', function () {
    $this->actingAs(User::factory()->admin()->create());

    $location = Location::factory()->create(['minimum_wage' => 15.00]);
    $employment = Employment::factory()->create();
    Assignment::factory()->for($employment)->create(['location_id' => $location->id, 'is_current' => true]);

    $response = $this->postJson("/api/v1/employees/{$employment->id}/compensation", [
        'pay_type' => 'salary', 'rate_amount' => 40000, 'pay_frequency' => 'annual',
        'effective_date' => now()->toDateString(), 'reason' => 'new_hire',
    ]);

    $response->assertCreated();
});
