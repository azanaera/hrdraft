<?php

namespace App\Domain\Offboarding\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Timeline\Services\TimelineRecorder;
use App\Domain\TimeOff\Models\TimeOffBalance;

/**
 * Calculates the payout-eligible figure for a terminated employee's unused
 * time off, at their current rate. This is NOT an actual payment — real
 * payroll (gross-to-net, tax withholding) is explicitly deferred — it's the
 * calculated number HR acts on manually until a payroll engine exists.
 */
class FinalPayoutService
{
    public function __construct(private readonly TimelineRecorder $timeline)
    {
    }

    public function calculate(Employment $employment): array
    {
        $compensation = $employment->currentCompensation()->first();
        $hourlyRate = $this->hourlyEquivalent($compensation);

        $balances = TimeOffBalance::where('employment_id', $employment->id)
            ->where('balance_hours', '>', 0)
            ->with('policy')
            ->get();

        $lineItems = $balances->map(fn ($balance) => [
            'policy' => $balance->policy->name,
            'hours' => (float) $balance->balance_hours,
            'rate' => $hourlyRate,
            'amount' => round((float) $balance->balance_hours * $hourlyRate, 2),
        ]);

        $totalAmount = round($lineItems->sum('amount'), 2);

        $this->timeline->record(
            person: $employment->person,
            employment: $employment,
            eventType: 'final_payout_calculated',
            summary: "Final payout calculated: \${$totalAmount} for ".$lineItems->sum('hours').' unused time-off hours.',
            payload: ['line_items' => $lineItems->toArray(), 'total_amount' => $totalAmount],
            visibility: 'admin_only',
        );

        return ['line_items' => $lineItems->toArray(), 'total_amount' => $totalAmount];
    }

    private function hourlyEquivalent(?\App\Domain\Compensation\Models\CompensationRecord $compensation): float
    {
        if (! $compensation) {
            return 0.0;
        }

        if ($compensation->pay_type === 'hourly') {
            return (float) $compensation->rate_amount;
        }

        // Salaried: rate_amount is the amount per pay_frequency period —
        // annualize it, then convert to an hourly equivalent (40hr/wk * 52wk).
        $annualized = match ($compensation->pay_frequency) {
            'annual' => (float) $compensation->rate_amount,
            'monthly' => (float) $compensation->rate_amount * 12,
            'semimonthly' => (float) $compensation->rate_amount * 24,
            'biweekly' => (float) $compensation->rate_amount * 26,
            'weekly' => (float) $compensation->rate_amount * 52,
            default => (float) $compensation->rate_amount,
        };

        return round($annualized / 2080, 2);
    }
}
