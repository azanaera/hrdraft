<?php

namespace App\Domain\Timeline\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Person;
use App\Domain\Timeline\Models\EmployeeEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class TimelineRecorder
{
    /**
     * Every module writes into the employee timeline through this single
     * entry point, so the event log stays a consistent, human-readable
     * append-only trail regardless of which module triggered it.
     */
    public function record(
        Person $person,
        ?Employment $employment,
        string $eventType,
        string $summary,
        array $payload = [],
        string $visibility = 'manager_and_above',
        ?string $eventDate = null,
    ): EmployeeEvent {
        return EmployeeEvent::create([
            'person_id' => $person->id,
            'employment_id' => $employment?->id,
            'event_type' => $eventType,
            'event_date' => $eventDate ?? Carbon::now()->toDateString(),
            'summary' => $summary,
            'payload' => $payload,
            'actor_user_id' => Auth::id(),
            'visibility' => $visibility,
        ]);
    }
}
