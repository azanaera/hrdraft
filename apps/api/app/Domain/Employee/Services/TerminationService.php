<?php

namespace App\Domain\Employee\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Offboarding\Models\OffboardingTemplate;
use App\Domain\Offboarding\Services\OffboardingWorkflowService;
use App\Domain\Timeline\Services\TimelineRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TerminationService
{
    public function __construct(
        private readonly TimelineRecorder $timeline,
        private readonly OffboardingWorkflowService $offboarding,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Terminates an employment: flips status, revokes the associated user's
     * access immediately (tokens + any active database sessions — the
     * EnsureEmploymentActive middleware is the backstop for anything this
     * misses), records the event, and starts the offboarding workflow.
     */
    public function terminate(Employment $employment, string $terminationDate, string $reason): Employment
    {
        return DB::transaction(function () use ($employment, $terminationDate, $reason) {
            $employment->update([
                'employment_status' => 'terminated',
                'termination_date' => $terminationDate,
                'termination_reason' => $reason,
            ]);

            $this->revokeAccess($employment);

            $this->timeline->record(
                person: $employment->person,
                employment: $employment,
                eventType: 'terminated',
                summary: "Terminated effective {$terminationDate}. Reason: {$reason}.",
                payload: ['reason' => $reason],
                visibility: 'all_hr',
            );

            $template = OffboardingTemplate::where('is_active', true)->first();
            if ($template) {
                $this->offboarding->start($employment, $template);
            }

            $this->notifyStakeholders($employment, $reason);

            return $employment->fresh();
        });
    }

    private function revokeAccess(Employment $employment): void
    {
        $user = User::where('employment_id', $employment->id)->first();

        if (! $user) {
            return;
        }

        $user->tokens()->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function notifyStakeholders(Employment $employment, string $reason): void
    {
        $currentAssignment = $employment->currentAssignment()->first();
        $managerUser = $currentAssignment?->manager_employment_id
            ? User::where('employment_id', $currentAssignment->manager_employment_id)->first()
            : null;

        $recipients = User::whereIn('role', ['admin', 'hr_manager'])->get();

        if ($managerUser) {
            $recipients->push($managerUser);
        }

        foreach ($recipients->unique('id') as $recipient) {
            $this->notifications->notify(
                user: $recipient,
                type: 'termination',
                title: 'Employee terminated',
                body: "{$employment->person->fullName()} (#{$employment->employee_number}) was terminated. Reason: {$reason}.",
                relatedEmploymentId: $employment->id,
            );
        }
    }
}
