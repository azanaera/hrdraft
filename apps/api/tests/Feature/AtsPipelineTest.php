<?php

use App\Domain\ATS\Models\Application;
use App\Domain\ATS\Models\JobRequisition;
use App\Domain\ATS\Models\PipelineStage;
use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Position;
use App\Models\User;

function atsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    test()->actingAs($admin);

    return $admin;
}

it('creates a candidate and application with an initial stage-history row', function () {
    atsAdmin();

    $department = Department::factory()->create();
    $location = Location::factory()->create();
    $stage = PipelineStage::first() ?? PipelineStage::factory()->create(['order' => 1]);

    $requisition = JobRequisition::factory()->create([
        'department_id' => $department->id,
        'location_id' => $location->id,
    ]);

    $response = $this->postJson('/api/v1/ats/candidates', [
        'first_name' => 'Jordan',
        'last_name' => 'Reyes',
        'email' => 'jordan.reyes+test@example.com',
        'requisition_id' => $requisition->id,
    ]);

    $response->assertCreated();

    $application = Application::where('requisition_id', $requisition->id)->firstOrFail();
    expect($application->stageHistory()->count())->toBe(1);
    expect($application->stageHistory()->first()->stage_id)->toBe($application->current_stage_id);
});

it('records a new stage-history row and closes the previous one on move-stage', function () {
    atsAdmin();

    $department = Department::factory()->create();
    $location = Location::factory()->create();
    PipelineStage::first() ?? PipelineStage::factory()->create(['order' => 1]);
    $requisition = JobRequisition::factory()->create([
        'department_id' => $department->id,
        'location_id' => $location->id,
    ]);

    $created = $this->postJson('/api/v1/ats/candidates', [
        'first_name' => 'Alex',
        'last_name' => 'Chen',
        'email' => 'alex.chen+test@example.com',
        'requisition_id' => $requisition->id,
    ])->assertCreated();

    $applicationId = $created->json('data.id');
    $currentStageId = Application::findOrFail($applicationId)->current_stage_id;
    $nextStage = PipelineStage::where('id', '!=', $currentStageId)->orderBy('order')->first()
        ?? PipelineStage::factory()->create(['order' => 99]);

    $this->postJson("/api/v1/ats/applications/{$applicationId}/move-stage", [
        'stage_id' => $nextStage->id,
    ])->assertOk();

    $application = Application::findOrFail($applicationId);
    expect($application->current_stage_id)->toBe($nextStage->id);
    expect($application->stageHistory()->count())->toBe(2);
    expect($application->stageHistory()->whereNull('exited_at')->count())->toBe(1);
});

it('rejects a requisition without a position, since hiring from one always needs one', function () {
    atsAdmin();

    $department = Department::factory()->create();
    $location = Location::factory()->create();

    $this->postJson('/api/v1/ats/requisitions', [
        'title' => 'Floor Supervisor',
        'department_id' => $department->id,
        'location_id' => $location->id,
        'employment_type' => 'hourly',
    ])->assertUnprocessable()->assertJsonValidationErrors('position_id');
});

it('hires a candidate straight through, using the requisition position for the new assignment', function () {
    atsAdmin();

    $department = Department::factory()->create();
    $location = Location::factory()->create();
    $position = Position::factory()->create(['department_id' => $department->id]);
    PipelineStage::first() ?? PipelineStage::factory()->create(['order' => 1]);

    $requisition = JobRequisition::factory()->create([
        'department_id' => $department->id,
        'location_id' => $location->id,
        'position_id' => $position->id,
    ]);

    $created = $this->postJson('/api/v1/ats/candidates', [
        'first_name' => 'Riley',
        'last_name' => 'Park',
        'email' => 'riley.park+test@example.com',
        'requisition_id' => $requisition->id,
    ])->assertCreated();

    $applicationId = $created->json('data.id');

    $hireResponse = $this->postJson("/api/v1/ats/applications/{$applicationId}/hire", [
        'employee_number' => 'E-ATS-HIRE-1',
        'hire_date' => now()->toDateString(),
        'employment_type' => 'hourly',
        'pay_type' => 'hourly',
        'rate_amount' => 20,
        'pay_frequency' => 'biweekly',
    ])->assertCreated();

    $employmentId = $hireResponse->json('employment.id');
    $employment = \App\Domain\Employee\Models\Employment::with('currentAssignment')->findOrFail($employmentId);
    expect($employment->currentAssignment->position_id)->toBe($position->id);
});

it('does not mark a brand-new hire as a former employee, only a genuine email-matched rehire', function () {
    atsAdmin();

    $department = Department::factory()->create();
    $location = Location::factory()->create();
    $position = Position::factory()->create(['department_id' => $department->id]);
    PipelineStage::first() ?? PipelineStage::factory()->create(['order' => 1]);

    $requisition = JobRequisition::factory()->create([
        'department_id' => $department->id,
        'location_id' => $location->id,
        'position_id' => $position->id,
    ]);

    $newHire = $this->postJson('/api/v1/ats/candidates', [
        'first_name' => 'Taylor',
        'last_name' => 'Novak',
        'email' => 'taylor.novak+brandnew@example.com',
        'requisition_id' => $requisition->id,
    ])->assertCreated();

    expect($newHire->json('data.candidate.is_former_employee'))->toBeFalse();

    $this->postJson("/api/v1/ats/applications/{$newHire->json('data.id')}/hire", [
        'employee_number' => 'E-ATS-HIRE-2',
        'hire_date' => now()->toDateString(),
        'employment_type' => 'hourly',
        'pay_type' => 'hourly',
        'rate_amount' => 20,
        'pay_frequency' => 'biweekly',
    ])->assertCreated();

    // Re-fetch through the same list endpoint the UI uses, post-hire.
    $afterHire = $this->getJson("/api/v1/ats/applications?requisition_id={$requisition->id}")->assertOk();
    expect($afterHire->json('data.0.candidate.is_former_employee'))->toBeFalse();

    // A second candidate re-applying with an email that matches an already-
    // existing person IS a genuine former employee, and should say so
    // immediately at creation time — before any hire happens.
    $existingPerson = \App\Domain\Employee\Models\Person::factory()->create([
        'personal_email' => 'returning.person@example.com',
    ]);

    $rehireApplication = $this->postJson('/api/v1/ats/candidates', [
        'first_name' => $existingPerson->first_name,
        'last_name' => $existingPerson->last_name,
        'email' => 'returning.person@example.com',
        'requisition_id' => $requisition->id,
    ])->assertCreated();

    expect($rehireApplication->json('data.candidate.is_former_employee'))->toBeTrue();
});
