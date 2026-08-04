<?php

namespace App\Domain\Compensation\Services;

use App\Domain\Compensation\Models\CompensationRecord;
use App\Domain\Employee\Models\Employment;
use App\Domain\Timeline\Services\TimelineRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompensationService
{
    public function __construct(private readonly TimelineRecorder $timeline)
    {
    }

    /**
     * The only write path for compensation. Closes the currently-open record
     * (if any) the day before the new one starts, then inserts the new
     * record — so the DB's no-overlap exclusion constraint is never violated
     * by application logic.
     */
    public function applyChange(Employment $employment, array $data): CompensationRecord
    {
        $this->assertMeetsMinimumWage($employment, $data);

        return DB::transaction(function () use ($employment, $data) {
            $open = $employment->currentCompensation()->first();

            if ($open) {
                $dayBefore = \Illuminate\Support\Carbon::parse($data['effective_date'])->subDay()->toDateString();
                $open->update(['end_date' => $dayBefore]);
            }

            $record = $employment->compensationRecords()->create([
                'pay_type' => $data['pay_type'],
                'rate_amount' => $data['rate_amount'],
                'pay_frequency' => $data['pay_frequency'],
                'currency' => $data['currency'] ?? 'USD',
                'effective_date' => $data['effective_date'],
                'end_date' => null,
                'reason' => $data['reason'],
                'related_assignment_id' => $data['related_assignment_id'] ?? null,
                'approved_by_user_id' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->timeline->record(
                person: $employment->person,
                employment: $employment,
                eventType: 'comp_changed',
                summary: sprintf(
                    'Compensation changed to %s %s (%s), effective %s.',
                    $data['rate_amount'],
                    $data['pay_type'],
                    $data['reason'],
                    $data['effective_date'],
                ),
                payload: ['compensation_record_id' => $record->id, 'reason' => $data['reason']],
                visibility: 'admin_only',
            );

            return $record;
        });
    }

    /**
     * Multi-state minimum wage matters now per the spec — validated against
     * the employment's current assignment location, since wage law is tied
     * to where the employee physically works, not their role.
     */
    private function assertMeetsMinimumWage(Employment $employment, array $data): void
    {
        if (($data['pay_type'] ?? null) !== 'hourly') {
            return;
        }

        $location = $employment->currentAssignment()->first()?->location;

        if (! $location || $location->minimum_wage === null) {
            return;
        }

        if ((float) $data['rate_amount'] < (float) $location->minimum_wage) {
            throw new WageComplianceException(
                "Rate {$data['rate_amount']} is below {$location->name}'s minimum wage of {$location->minimum_wage}."
            );
        }
    }
}
