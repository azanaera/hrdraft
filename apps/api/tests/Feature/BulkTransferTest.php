<?php

use App\Domain\Employee\Models\Assignment;
use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Position;
use App\Models\User;

it('transfers multiple employees at once', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employmentA = Employment::factory()->create();
    Assignment::factory()->for($employmentA)->create(['is_current' => true]);
    $employmentB = Employment::factory()->create();
    Assignment::factory()->for($employmentB)->create(['is_current' => true]);

    $newDept = Department::factory()->create();
    $newLocation = Location::factory()->create();
    $newPosition = Position::factory()->create(['department_id' => $newDept->id]);

    $response = $this->postJson('/api/v1/employees/bulk-transfer', [
        'employment_ids' => [$employmentA->id, $employmentB->id],
        'department_id' => $newDept->id,
        'location_id' => $newLocation->id,
        'position_id' => $newPosition->id,
        'effective_start_date' => now()->toDateString(),
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.succeeded', [$employmentA->id, $employmentB->id]);
    expect($employmentA->currentAssignment()->first()->department_id)->toBe($newDept->id);
    expect($employmentB->currentAssignment()->first()->department_id)->toBe($newDept->id);
});

it('blocks non-back-office roles from bulk transfer', function () {
    $employment = Employment::factory()->create();
    $user = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/employees/bulk-transfer', [
        'employment_ids' => [$employment->id],
        'department_id' => Department::factory()->create()->id,
        'location_id' => Location::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'effective_start_date' => now()->toDateString(),
    ]);

    $response->assertStatus(403);
});
