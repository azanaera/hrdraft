<?php

namespace App\Domain\Onboarding\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Onboarding\Models\OnboardingTask;
use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Domain\Onboarding\Models\OnboardingWorkflow;
use App\Domain\Timeline\Services\TimelineRecorder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OnboardingWorkflowService
{
    public function __construct(
        private readonly TimelineRecorder $timeline,
        private readonly NotificationService $notifications,
        private readonly BackgroundCheckService $backgroundChecks,
    ) {
    }

    /**
     * Starts an onboarding workflow for a new (or rehired) employment,
     * instantiating one OnboardingTask per template task. Called by
     * HireService/RehireService/HireCandidateService on hire.
     */
    public function start(Employment $employment, OnboardingTemplate $template): OnboardingWorkflow
    {
        return DB::transaction(function () use ($employment, $template) {
            $workflow = OnboardingWorkflow::create([
                'employment_id' => $employment->id,
                'template_id' => $template->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            foreach ($template->tasks as $templateTask) {
                OnboardingTask::create([
                    'workflow_id' => $workflow->id,
                    'template_task_id' => $templateTask->id,
                    'title' => $templateTask->title,
                    'task_type' => $templateTask->task_type,
                    'status' => 'pending',
                ]);
            }

            $this->timeline->record(
                person: $employment->person,
                employment: $employment,
                eventType: 'onboarding_started',
                summary: "Onboarding started ({$template->name}).",
                payload: ['workflow_id' => $workflow->id],
            );

            // Background check + E-Verify (I-9) run automatically as part of
            // onboarding, per the spec — real high-volume hourly hiring
            // needs both, not just a manual HR step.
            $this->backgroundChecks->run($employment, 'background_check');
            $this->backgroundChecks->run($employment, 'e_verify');

            return $workflow->load('tasks');
        });
    }

    public function completeTask(OnboardingTask $task, ?int $relatedDocumentId = null): OnboardingTask
    {
        return DB::transaction(function () use ($task, $relatedDocumentId) {
            $task->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by_user_id' => Auth::id(),
                'related_document_id' => $relatedDocumentId,
            ]);

            $workflow = $task->workflow;
            $remaining = $workflow->tasks()->where('status', '!=', 'completed')->where('status', '!=', 'waived')->count();

            if ($remaining === 0) {
                $workflow->update(['status' => 'completed', 'completed_at' => now()]);

                $this->timeline->record(
                    person: $workflow->employment->person,
                    employment: $workflow->employment,
                    eventType: 'onboarding_task_completed',
                    summary: 'Onboarding completed — all tasks finished.',
                    payload: ['workflow_id' => $workflow->id],
                );

                foreach (User::whereIn('role', ['admin', 'hr_manager'])->get() as $recipient) {
                    $this->notifications->notify(
                        user: $recipient,
                        type: 'onboarding_completed',
                        title: 'Onboarding completed',
                        body: "{$workflow->employment->person->fullName()} has completed onboarding.",
                        relatedEmploymentId: $workflow->employment_id,
                    );
                }
            }

            return $task;
        });
    }
}
