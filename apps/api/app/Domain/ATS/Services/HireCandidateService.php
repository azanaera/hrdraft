<?php

namespace App\Domain\ATS\Services;

use App\Domain\ATS\Models\Application;
use App\Domain\Compensation\Services\CompensationService;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Person;
use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Domain\Onboarding\Services\OnboardingWorkflowService;
use App\Domain\Timeline\Services\TimelineRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The ONLY write path from ATS into core employee data. If a third-party ATS
 * ever replaces this module, only this service's call site (or an equivalent
 * webhook receiver implementing the same contract) needs to exist — every
 * other ATS table can be dropped independently without touching Employee,
 * Compensation, or Onboarding code.
 */
class HireCandidateService
{
    public function __construct(
        private readonly CompensationService $compensation,
        private readonly TimelineRecorder $timeline,
        private readonly OnboardingWorkflowService $onboarding,
    ) {
    }

    public function hire(Application $application, array $data): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $candidate = $application->candidate;

            // Dedupe against `people` by email so a former employee re-applying
            // (a "frequent rehire") is detected rather than duplicated.
            $person = $candidate->linkedPerson
                ?? Person::where('personal_email', $candidate->email)->first()
                ?? Person::create([
                    'person_number' => 'P-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
                    'first_name' => $candidate->first_name,
                    'last_name' => $candidate->last_name,
                    'personal_email' => $candidate->email,
                    'phone' => $candidate->phone,
                ]);

            if (! $candidate->linked_person_id) {
                $candidate->update(['linked_person_id' => $person->id]);
            }

            $employment = $person->employments()->create([
                'employee_number' => $data['employee_number'],
                'hire_date' => $data['hire_date'],
                'employment_status' => 'active',
                'employment_type' => $data['employment_type'],
                'rehire_eligible' => true,
            ]);

            $employment->assignments()->create([
                'department_id' => $application->requisition->department_id,
                'location_id' => $application->requisition->location_id,
                'position_id' => $data['position_id'] ?? $application->requisition->position_id,
                'manager_employment_id' => $data['manager_employment_id'] ?? null,
                'effective_start_date' => $data['hire_date'],
                'effective_end_date' => null,
                'is_current' => true,
                'created_by_user_id' => Auth::id(),
            ]);

            $this->compensation->applyChange($employment, [
                'pay_type' => $data['pay_type'],
                'rate_amount' => $data['rate_amount'],
                'pay_frequency' => $data['pay_frequency'],
                'effective_date' => $data['hire_date'],
                'reason' => 'new_hire',
            ]);

            $application->update([
                'status' => 'hired',
                'hired_employment_id' => $employment->id,
            ]);

            $application->requisition()->update(['status' => 'filled', 'closed_at' => now()]);

            $this->timeline->record(
                person: $person,
                employment: $employment,
                eventType: 'hired',
                summary: "Hired via ATS from application #{$application->id} ({$application->requisition->title}).",
                payload: ['application_id' => $application->id, 'requisition_id' => $application->requisition_id],
                visibility: 'all_hr',
            );

            $this->startOnboardingIfTemplateExists($employment);

            return $application->fresh(['candidate', 'hiredEmployment']);
        });
    }

    /** Same matching logic as HireService — prefers a template matching employment_type, falls back to any active one. */
    private function startOnboardingIfTemplateExists(Employment $employment): void
    {
        $template = OnboardingTemplate::where('is_active', true)
            ->where(fn ($q) => $q->where('applicable_employment_type', $employment->employment_type)->orWhereNull('applicable_employment_type'))
            ->orderByRaw('applicable_employment_type IS NULL')
            ->first();

        if ($template) {
            $this->onboarding->start($employment, $template);
        }
    }
}
