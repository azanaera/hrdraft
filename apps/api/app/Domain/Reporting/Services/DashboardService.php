<?php

namespace App\Domain\Reporting\Services;

use App\Domain\ATS\Models\JobRequisition;
use App\Domain\Employee\Models\Employment;
use App\Domain\Timeline\Models\EmployeeEvent;
use App\Domain\TimeOff\Models\TimeOffRequest;

class DashboardService
{
    public function summary(): array
    {
        $recentHires = EmployeeEvent::where('event_type', 'hired')
            ->with('employment.person')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn ($event) => [
                'employment_id' => $event->employment_id,
                'name' => $event->employment?->person?->fullName(),
                'date' => $event->event_date?->toDateString(),
            ]);

        return [
            'headcount' => Employment::where('employment_status', '!=', 'terminated')->count(),
            'open_requisitions' => JobRequisition::where('status', 'open')->count(),
            'pending_time_off_requests' => TimeOffRequest::where('status', 'pending')->count(),
            'recent_hires' => $recentHires,
        ];
    }
}
