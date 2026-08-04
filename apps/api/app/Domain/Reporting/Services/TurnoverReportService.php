<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Person;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * All figures are derived from existing employments/people data — no new
 * tables, exactly as the spec notes (the gap was a reporting UI, not
 * missing data).
 */
class TurnoverReportService
{
    public function summary(?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from) : Carbon::now()->subYear();
        $to = $to ? Carbon::parse($to) : Carbon::now();

        $terminations = Employment::whereNotNull('termination_date')
            ->whereBetween('termination_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $activeCount = Employment::where('employment_status', '!=', 'terminated')->count();

        $byDepartment = $this->terminationsByDimension($terminations, 'department');
        $byLocation = $this->terminationsByDimension($terminations, 'location');
        $byReason = $terminations->groupBy('termination_reason')
            ->map(fn ($group, $reason) => ['reason' => $reason ?: 'Not specified', 'count' => $group->count()])
            ->values();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'active_headcount' => $activeCount,
            'termination_count' => $terminations->count(),
            'turnover_rate' => $activeCount > 0 ? round($terminations->count() / $activeCount * 100, 1) : 0,
            'average_tenure_days' => $this->averageTenureDays(),
            'rehire_rate' => $this->rehireRate(),
            'terminations_by_department' => $byDepartment,
            'terminations_by_location' => $byLocation,
            'terminations_by_reason' => $byReason,
        ];
    }

    private function terminationsByDimension($terminations, string $dimension): array
    {
        $counts = [];

        foreach ($terminations as $employment) {
            // Use the last assignment on record (current or most recently closed).
            $assignment = $employment->assignments()->orderByDesc('effective_start_date')->first();
            $label = match ($dimension) {
                'department' => $assignment?->department?->name ?? 'Unknown',
                'location' => $assignment?->location?->name ?? 'Unknown',
                default => 'Unknown',
            };

            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        return collect($counts)->map(fn ($count, $label) => ['label' => $label, 'count' => $count])->values()->all();
    }

    private function averageTenureDays(): float
    {
        $rows = Employment::query()
            ->selectRaw('hire_date, COALESCE(termination_date, CURRENT_DATE) as end_date')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $totalDays = $rows->sum(fn ($row) => Carbon::parse($row->hire_date)->diffInDays(Carbon::parse($row->end_date)));

        return round($totalDays / $rows->count(), 1);
    }

    private function rehireRate(): float
    {
        $totalPeople = Person::count();

        if ($totalPeople === 0) {
            return 0;
        }

        $rehiredPeople = DB::table('employments')
            ->select('person_id')
            ->groupBy('person_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return round($rehiredPeople / $totalPeople * 100, 1);
    }
}
