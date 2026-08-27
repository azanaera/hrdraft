<?php

use App\Domain\Employee\Models\Employment;
use App\Domain\TimeOff\Models\TimeOffBalance;
use App\Domain\TimeOff\Models\TimeOffLedgerEntry;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Domain\TimeOff\Services\TimeOffAccrualService;

it('posts a first accrual entry once a full period has elapsed since hire', function () {
    $policy = TimeOffPolicy::factory()->create(['accrual_rate' => 3.0, 'max_balance' => 120]);
    $employment = Employment::factory()->create(['hire_date' => now()->subDays(20)->toDateString()]);

    $posted = app(TimeOffAccrualService::class)->run();

    expect($posted)->toHaveCount(1);
    expect($posted[0]['employment_id'])->toBe($employment->id);
    expect((float) $posted[0]['hours'])->toBe(3.0);

    $balance = TimeOffBalance::where('employment_id', $employment->id)->where('policy_id', $policy->id)->first();
    expect((float) $balance->balance_hours)->toBe(3.0);
});

it('does not accrue again before a full period has elapsed', function () {
    $policy = TimeOffPolicy::factory()->create();
    $employment = Employment::factory()->create(['hire_date' => now()->subDays(20)->toDateString()]);

    app(TimeOffAccrualService::class)->run();
    $secondRun = app(TimeOffAccrualService::class)->run();

    expect($secondRun)->toBeEmpty();
    expect(TimeOffLedgerEntry::where('employment_id', $employment->id)->count())->toBe(1);
});

it('caps accrual at the policy max balance instead of exceeding it', function () {
    $policy = TimeOffPolicy::factory()->create(['accrual_rate' => 3.0, 'max_balance' => 5]);
    $employment = Employment::factory()->create(['hire_date' => now()->subDays(20)->toDateString()]);

    TimeOffLedgerEntry::create([
        'employment_id' => $employment->id,
        'policy_id' => $policy->id,
        'entry_type' => 'adjustment',
        'hours' => 4,
        'effective_date' => now()->subDays(19)->toDateString(),
    ]);
    app(\App\Domain\TimeOff\Services\TimeOffService::class)->recalculateBalance($employment->id, $policy->id);

    $posted = app(TimeOffAccrualService::class)->run();

    expect($posted)->toHaveCount(1);
    expect((float) $posted[0]['hours'])->toBe(1.0); // capped: 5 max - 4 existing = 1

    $balance = TimeOffBalance::where('employment_id', $employment->id)->where('policy_id', $policy->id)->first();
    expect((float) $balance->balance_hours)->toBe(5.0);
});

it('never accrues a policy with accrual_method none', function () {
    TimeOffPolicy::factory()->create(['accrual_method' => 'none']);
    Employment::factory()->create(['hire_date' => now()->subYear()->toDateString()]);

    $posted = app(TimeOffAccrualService::class)->run();

    expect($posted)->toBeEmpty();
});

it('skips a terminated employment', function () {
    TimeOffPolicy::factory()->create();
    Employment::factory()->terminated()->create(['hire_date' => now()->subYear()->toDateString()]);

    $posted = app(TimeOffAccrualService::class)->run();

    expect($posted)->toBeEmpty();
});

it('dry-run reports what would post without writing anything', function () {
    TimeOffPolicy::factory()->create();
    $employment = Employment::factory()->create(['hire_date' => now()->subDays(20)->toDateString()]);

    $posted = app(TimeOffAccrualService::class)->run(dryRun: true);

    expect($posted)->toHaveCount(1);
    expect(TimeOffLedgerEntry::where('employment_id', $employment->id)->count())->toBe(0);
    expect(TimeOffBalance::where('employment_id', $employment->id)->exists())->toBeFalse();
});

it('the scheduled command posts accruals and supports --dry-run', function () {
    TimeOffPolicy::factory()->create();
    Employment::factory()->create(['hire_date' => now()->subDays(20)->toDateString()]);

    $this->artisan('time-off:accrue', ['--dry-run' => true])
        ->assertExitCode(0);

    expect(TimeOffLedgerEntry::count())->toBe(0);

    $this->artisan('time-off:accrue')
        ->assertExitCode(0);

    expect(TimeOffLedgerEntry::count())->toBe(1);
});
