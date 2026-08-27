<?php

namespace App\Domain\TimeOff\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\TimeOff\Models\TimeOffBalance;
use App\Domain\TimeOff\Models\TimeOffLedgerEntry;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Posts scheduled accrual ledger entries for every active policy/employment
 * pair that's due. Intended to run once a day (see routes/console.php) — it's
 * safe to run more or less often since it only posts an entry once a full
 * period has actually elapsed since the last one.
 *
 * Deliberately conservative on first run for an employment with no prior
 * accrual history: it posts exactly one period's worth, not a back-accrued
 * catch-up for every period since hire. Backfilling historical accrual for
 * employees hired before this job existed is a one-time data-migration
 * concern, not something this job should guess at silently.
 */
class TimeOffAccrualService
{
    private const PAY_FREQUENCY_DAYS = [
        'weekly' => 7,
        'biweekly' => 14,
        'semimonthly' => 15,
        'monthly' => 30,
        'annual' => 365,
    ];

    public function run(bool $dryRun = false): array
    {
        $posted = [];

        $policies = TimeOffPolicy::where('is_active', true)
            ->where('accrual_method', '!=', 'none')
            ->get();

        foreach ($policies as $policy) {
            $employments = Employment::where('employment_status', 'active')
                ->when($policy->applies_to !== 'all', fn ($q) => $q->where('employment_type', $policy->applies_to))
                ->get();

            foreach ($employments as $employment) {
                $result = $this->accrueIfDue($employment, $policy, $dryRun);

                if ($result !== null) {
                    $posted[] = $result;
                }
            }
        }

        return $posted;
    }

    private function accrueIfDue(Employment $employment, TimeOffPolicy $policy, bool $dryRun): ?array
    {
        $lastAccrualDate = TimeOffLedgerEntry::where('employment_id', $employment->id)
            ->where('policy_id', $policy->id)
            ->where('entry_type', 'accrual')
            ->max('effective_date');

        $sinceDate = $lastAccrualDate ? Carbon::parse($lastAccrualDate) : Carbon::parse($employment->hire_date);
        $periodDays = $this->periodDays($employment, $policy);

        if ($sinceDate->diffInDays(now()) < $periodDays) {
            return null;
        }

        $currentBalance = (float) (TimeOffBalance::where('employment_id', $employment->id)
            ->where('policy_id', $policy->id)
            ->value('balance_hours') ?? 0);

        $amount = (float) $policy->accrual_rate;

        if ($policy->max_balance !== null) {
            $amount = min($amount, max(0, (float) $policy->max_balance - $currentBalance));
        }

        if ($amount <= 0) {
            return null;
        }

        $summary = [
            'employment_id' => $employment->id,
            'employee_number' => $employment->employee_number,
            'policy' => $policy->name,
            'hours' => $amount,
        ];

        if ($dryRun) {
            return $summary;
        }

        DB::transaction(function () use ($employment, $policy, $amount) {
            TimeOffLedgerEntry::create([
                'employment_id' => $employment->id,
                'policy_id' => $policy->id,
                'entry_type' => 'accrual',
                'hours' => $amount,
                'effective_date' => now()->toDateString(),
                'notes' => 'Scheduled accrual',
            ]);

            app(TimeOffService::class)->recalculateBalance($employment->id, $policy->id);
        });

        return $summary;
    }

    private function periodDays(Employment $employment, TimeOffPolicy $policy): int
    {
        if ($policy->accrual_method === 'fixed_annual') {
            return 365;
        }

        // per_pay_period: key off the employment's actual pay frequency so
        // accrual cadence matches how often they're actually paid, not a
        // single global assumption. Falls back to biweekly — the most common
        // frequency in this workforce — if no compensation record exists yet.
        $payFrequency = $employment->currentCompensation?->pay_frequency;

        return self::PAY_FREQUENCY_DAYS[$payFrequency] ?? self::PAY_FREQUENCY_DAYS['biweekly'];
    }
}
