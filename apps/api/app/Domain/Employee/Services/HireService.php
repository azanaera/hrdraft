<?php

namespace App\Domain\Employee\Services;

use App\Domain\Compensation\Services\CompensationService;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Person;
use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Domain\Onboarding\Services\OnboardingWorkflowService;
use App\Domain\Timeline\Services\TimelineRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HireService
{
    public function __construct(
        private readonly CompensationService $compensation,
        private readonly TimelineRecorder $timeline,
        private readonly OnboardingWorkflowService $onboarding,
    ) {
    }

    /**
     * Direct hire (not via ATS): creates/reuses the person, a new employment,
     * an initial assignment, and an initial compensation record in one
     * transaction. This is the same shape of work App\Domain\ATS\Services\
     * HireCandidateService performs when a candidate converts to an employee.
     */
    public function hire(array $data): Employment
    {
        return DB::transaction(function () use ($data) {
            $person = isset($data['person_id'])
                ? Person::findOrFail($data['person_id'])
                : Person::create([
                    'person_number' => 'P-'.Str::upper(Str::random(8)),
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'personal_email' => $data['personal_email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]);

            $employment = $person->employments()->create([
                'employee_number' => $data['employee_number'],
                'hire_date' => $data['hire_date'],
                'employment_status' => 'active',
                'employment_type' => $data['employment_type'],
                'rehire_eligible' => true,
            ]);

            $employment->assignments()->create([
                'department_id' => $data['department_id'],
                'location_id' => $data['location_id'],
                'position_id' => $data['position_id'],
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

            $this->timeline->record(
                person: $person,
                employment: $employment,
                eventType: 'hired',
                summary: "Hired as {$data['employment_type']} employee (#{$employment->employee_number}).",
                visibility: 'all_hr',
            );

            $this->startOnboardingIfTemplateExists($employment);

            return $employment->fresh(['person', 'currentAssignment', 'currentCompensation']);
        });
    }

    /**
     * Prefers a template matching the new hire's employment_type, falling
     * back to any active template. Silently does nothing if no template
     * exists yet (e.g. before an admin has configured one) — hiring must
     * not fail just because onboarding isn't set up.
     */
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
