<?php

namespace App\Domain\Employee\Services;

use App\Domain\Employee\Models\Assignment;
use App\Domain\Employee\Models\Employment;
use App\Domain\Timeline\Services\TimelineRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransferService
{
    public function __construct(private readonly TimelineRecorder $timeline)
    {
    }

    /**
     * Transfer an employment to a new department/location/position/manager.
     * Closes the current assignment and opens a new one — history is preserved,
     * never overwritten in place.
     */
    public function transfer(Employment $employment, array $data): Assignment
    {
        return DB::transaction(function () use ($employment, $data) {
            $effectiveDate = $data['effective_start_date'];

            $previous = $employment->currentAssignment()->first();

            if ($previous) {
                $previous->update([
                    'effective_end_date' => $effectiveDate,
                    'is_current' => false,
                ]);
            }

            $new = $employment->assignments()->create([
                'department_id' => $data['department_id'],
                'location_id' => $data['location_id'],
                'position_id' => $data['position_id'],
                'manager_employment_id' => $data['manager_employment_id'] ?? null,
                'effective_start_date' => $effectiveDate,
                'effective_end_date' => null,
                'is_current' => true,
                'created_by_user_id' => Auth::id(),
            ]);

            $this->timeline->record(
                person: $employment->person,
                employment: $employment,
                eventType: 'transferred',
                summary: "Transferred to a new assignment effective {$effectiveDate}.",
                payload: [
                    'previous_assignment_id' => $previous?->id,
                    'new_assignment_id' => $new->id,
                ],
            );

            return $new;
        });
    }
}
