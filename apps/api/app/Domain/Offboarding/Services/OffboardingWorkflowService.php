<?php

namespace App\Domain\Offboarding\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Offboarding\Models\OffboardingTask;
use App\Domain\Offboarding\Models\OffboardingTemplate;
use App\Domain\Offboarding\Models\OffboardingWorkflow;
use App\Domain\Timeline\Services\TimelineRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OffboardingWorkflowService
{
    public function __construct(
        private readonly TimelineRecorder $timeline,
        private readonly FinalPayoutService $finalPayout,
    ) {
    }

    public function start(Employment $employment, OffboardingTemplate $template): OffboardingWorkflow
    {
        return DB::transaction(function () use ($employment, $template) {
            $workflow = OffboardingWorkflow::create([
                'employment_id' => $employment->id,
                'template_id' => $template->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            foreach ($template->tasks as $templateTask) {
                OffboardingTask::create([
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
                eventType: 'offboarding_started',
                summary: "Offboarding started ({$template->name}).",
                payload: ['workflow_id' => $workflow->id],
            );

            // Calculate (not pay out) the final unused-time-off figure as soon
            // as offboarding starts, so it's available immediately for HR.
            $this->finalPayout->calculate($employment);

            return $workflow->load('tasks');
        });
    }

    public function completeTask(OffboardingTask $task): OffboardingTask
    {
        return DB::transaction(function () use ($task) {
            $task->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by_user_id' => Auth::id(),
            ]);

            $workflow = $task->workflow;
            $remaining = $workflow->tasks()->whereNotIn('status', ['completed', 'waived'])->count();

            if ($remaining === 0) {
                $workflow->update(['status' => 'completed', 'completed_at' => now()]);

                $this->timeline->record(
                    person: $workflow->employment->person,
                    employment: $workflow->employment,
                    eventType: 'offboarding_task_completed',
                    summary: 'Offboarding completed — all tasks finished.',
                    payload: ['workflow_id' => $workflow->id],
                );
            }

            return $task;
        });
    }
}
