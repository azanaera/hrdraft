<?php

namespace App\Domain\Employee\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Person;
use App\Domain\Timeline\Services\TimelineRecorder;
use Illuminate\Support\Facades\DB;

class RehireService
{
    public function __construct(private readonly TimelineRecorder $timeline)
    {
    }

    /**
     * Rehire an existing person. Always creates a brand-new `employments` row —
     * never reactivates or overwrites a prior stint — so employment history
     * (including the prior termination) stays intact.
     */
    public function rehire(Person $person, array $data): Employment
    {
        return DB::transaction(function () use ($person, $data) {
            $latest = $person->employments()->first();

            if ($latest && $latest->employment_status !== 'terminated') {
                throw new \DomainException('Person has an active employment; use transfer, not rehire.');
            }

            if ($latest && ! $latest->rehire_eligible) {
                throw new \DomainException('Person is not eligible for rehire.');
            }

            $employment = $person->employments()->create([
                'employee_number' => $data['employee_number'],
                'hire_date' => $data['hire_date'],
                'employment_status' => 'active',
                'employment_type' => $data['employment_type'],
                'rehire_eligible' => true,
            ]);

            $this->timeline->record(
                person: $person,
                employment: $employment,
                eventType: 'rehired',
                summary: "Rehired as of {$data['hire_date']} (employment #{$employment->id}).",
                payload: ['previous_employment_id' => $latest?->id],
            );

            return $employment;
        });
    }
}
