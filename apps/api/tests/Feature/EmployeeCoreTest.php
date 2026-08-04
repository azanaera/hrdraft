<?php

use App\Domain\Employee\Models\Assignment;
use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Person;
use App\Domain\Employee\Models\Position;
use App\Domain\Employee\Services\RehireService;
use App\Domain\Employee\Services\TransferService;
use App\Models\User;

function actingAsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    test()->actingAs($admin);

    return $admin;
}

it('creates a brand-new employment row on rehire, never updating the old one', function () {
    actingAsAdmin();

    $person = Person::factory()->create();
    $oldEmployment = Employment::factory()->terminated()->for($person)->create();

    $rehired = app(RehireService::class)->rehire($person, [
        'employee_number' => 'E-REHIRE-1',
        'hire_date' => now()->toDateString(),
        'employment_type' => 'hourly',
    ]);

    expect($rehired->id)->not->toBe($oldEmployment->id);
    expect($person->employments()->count())->toBe(2);
    expect($oldEmployment->fresh()->employment_status)->toBe('terminated');
    expect($rehired->employment_status)->toBe('active');
});

it('refuses to rehire someone who already has an active employment', function () {
    actingAsAdmin();

    $person = Person::factory()->create();
    Employment::factory()->for($person)->create(['employment_status' => 'active']);

    app(RehireService::class)->rehire($person, [
        'employee_number' => 'E-REHIRE-2',
        'hire_date' => now()->toDateString(),
        'employment_type' => 'hourly',
    ]);
})->throws(DomainException::class);

it('preserves assignment history on transfer instead of overwriting it', function () {
    actingAsAdmin();

    $employment = Employment::factory()->create();
    $originalAssignment = Assignment::factory()->for($employment)->create(['is_current' => true]);

    $newDept = Department::factory()->create();
    $newLocation = Location::factory()->create();
    $newPosition = Position::factory()->create(['department_id' => $newDept->id]);

    app(TransferService::class)->transfer($employment, [
        'department_id' => $newDept->id,
        'location_id' => $newLocation->id,
        'position_id' => $newPosition->id,
        'manager_employment_id' => null,
        'effective_start_date' => now()->toDateString(),
    ]);

    expect($originalAssignment->fresh()->is_current)->toBeFalse();
    expect($originalAssignment->fresh()->effective_end_date)->not->toBeNull();
    expect($employment->assignments()->count())->toBe(2);
    expect($employment->currentAssignment()->first()->department_id)->toBe($newDept->id);
});

it('records a timeline event for the transfer', function () {
    actingAsAdmin();

    $employment = Employment::factory()->create();
    Assignment::factory()->for($employment)->create(['is_current' => true]);

    $newDept = Department::factory()->create();
    $newLocation = Location::factory()->create();
    $newPosition = Position::factory()->create(['department_id' => $newDept->id]);

    app(TransferService::class)->transfer($employment, [
        'department_id' => $newDept->id,
        'location_id' => $newLocation->id,
        'position_id' => $newPosition->id,
        'manager_employment_id' => null,
        'effective_start_date' => now()->toDateString(),
    ]);

    expect($employment->person->events()->where('event_type', 'transferred')->count())->toBe(1);
});

it('prevents an employee role from viewing another employee record', function () {
    $employment = Employment::factory()->create();
    $otherEmployment = Employment::factory()->create();

    $user = User::factory()->create(['role' => 'employee', 'employment_id' => $employment->id]);
    $this->actingAs($user);

    $response = $this->getJson("/api/v1/employees/{$otherEmployment->id}");

    $response->assertStatus(403);
});

it('allows an hr manager to view any employee record', function () {
    $employment = Employment::factory()->create();
    $hr = User::factory()->hrManager()->create();
    $this->actingAs($hr);

    $response = $this->getJson("/api/v1/employees/{$employment->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $employment->id);
});
