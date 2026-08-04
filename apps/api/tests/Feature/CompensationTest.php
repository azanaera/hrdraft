<?php

use App\Domain\Compensation\Services\CompensationService;
use App\Domain\Employee\Models\Employment;
use App\Models\User;

it('closes the prior compensation record when a new one is applied', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employment = Employment::factory()->create();
    $service = app(CompensationService::class);

    $first = $service->applyChange($employment, [
        'pay_type' => 'hourly', 'rate_amount' => 18, 'pay_frequency' => 'biweekly',
        'effective_date' => now()->subMonths(6)->toDateString(), 'reason' => 'new_hire',
    ]);

    $second = $service->applyChange($employment, [
        'pay_type' => 'hourly', 'rate_amount' => 20, 'pay_frequency' => 'biweekly',
        'effective_date' => now()->toDateString(), 'reason' => 'raise',
    ]);

    expect($first->fresh()->end_date)->not->toBeNull();
    expect($second->end_date)->toBeNull();
    expect($employment->currentCompensation()->first()->id)->toBe($second->id);
});

it('rejects an overlapping compensation period at the database level', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employment = Employment::factory()->create();

    \App\Domain\Compensation\Models\CompensationRecord::create([
        'employment_id' => $employment->id,
        'pay_type' => 'hourly',
        'rate_amount' => 18,
        'pay_frequency' => 'biweekly',
        'effective_date' => now()->subMonths(2)->toDateString(),
        'end_date' => null,
        'reason' => 'new_hire',
    ]);

    // Directly inserting a second open-ended record for the same employment
    // (bypassing CompensationService) must be rejected by the DB constraint.
    \App\Domain\Compensation\Models\CompensationRecord::create([
        'employment_id' => $employment->id,
        'pay_type' => 'hourly',
        'rate_amount' => 22,
        'pay_frequency' => 'biweekly',
        'effective_date' => now()->subMonth()->toDateString(),
        'end_date' => null,
        'reason' => 'raise',
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('logs a comp_changed timeline event visible only to admins', function () {
    $this->actingAs(User::factory()->admin()->create());

    $employment = Employment::factory()->create();
    app(CompensationService::class)->applyChange($employment, [
        'pay_type' => 'salary', 'rate_amount' => 65000, 'pay_frequency' => 'annual',
        'effective_date' => now()->toDateString(), 'reason' => 'promotion',
    ]);

    $event = $employment->person->events()->where('event_type', 'comp_changed')->first();

    expect($event)->not->toBeNull();
    expect($event->visibility)->toBe('admin_only');
});
