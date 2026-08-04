<?php

namespace App\Domain\TimeOff\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Timeline\Services\TimelineRecorder;
use App\Domain\TimeOff\Models\TimeOffBalance;
use App\Domain\TimeOff\Models\TimeOffLedgerEntry;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Domain\TimeOff\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimeOffService
{
    public function __construct(
        private readonly TimelineRecorder $timeline,
        private readonly NotificationService $notifications,
    ) {
    }

    public function submitRequest(Employment $employment, TimeOffPolicy $policy, array $data): TimeOffRequest
    {
        return TimeOffRequest::create([
            'employment_id' => $employment->id,
            'policy_id' => $policy->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'hours_requested' => $data['hours_requested'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    /**
     * The single-level approver is either the employment's current manager
     * or any HR Manager/Admin — no multi-step approval chain at MVP.
     */
    public function canDecide(Employment $requesterEmployment, \App\Models\User $decider): bool
    {
        if ($decider->hasBackOfficeAccess()) {
            return true;
        }

        $currentAssignment = $requesterEmployment->currentAssignment()->first();

        return $currentAssignment && $decider->employment_id === $currentAssignment->manager_employment_id;
    }

    public function approve(TimeOffRequest $request, \App\Models\User $decider, ?string $notes = null): TimeOffRequest
    {
        return DB::transaction(function () use ($request, $decider, $notes) {
            $request->update([
                'status' => 'approved',
                'decided_by_user_id' => $decider->id,
                'decided_at' => now(),
                'decision_notes' => $notes,
            ]);

            $ledgerEntry = TimeOffLedgerEntry::create([
                'employment_id' => $request->employment_id,
                'policy_id' => $request->policy_id,
                'entry_type' => 'request_deduction',
                'hours' => -abs((float) $request->hours_requested),
                'effective_date' => $request->start_date,
                'related_request_id' => $request->id,
            ]);

            $this->recalculateBalance($request->employment_id, $request->policy_id);

            $this->timeline->record(
                person: $request->employment->person,
                employment: $request->employment,
                eventType: 'time_off_decided',
                summary: "Time off request approved for {$request->start_date->toDateString()} - {$request->end_date->toDateString()}.",
                payload: ['request_id' => $request->id, 'ledger_entry_id' => $ledgerEntry->id],
            );

            $this->notifyRequester($request, 'approved');

            return $request;
        });
    }

    public function deny(TimeOffRequest $request, \App\Models\User $decider, ?string $notes = null): TimeOffRequest
    {
        $request->update([
            'status' => 'denied',
            'decided_by_user_id' => $decider->id,
            'decided_at' => now(),
            'decision_notes' => $notes,
        ]);

        $this->timeline->record(
            person: $request->employment->person,
            employment: $request->employment,
            eventType: 'time_off_decided',
            summary: "Time off request denied for {$request->start_date->toDateString()} - {$request->end_date->toDateString()}.",
            payload: ['request_id' => $request->id],
        );

        $this->notifyRequester($request, 'denied');

        return $request;
    }

    private function notifyRequester(TimeOffRequest $request, string $decision): void
    {
        $requesterUser = User::where('employment_id', $request->employment_id)->first();

        if (! $requesterUser) {
            return;
        }

        $this->notifications->notify(
            user: $requesterUser,
            type: 'time_off_decided',
            title: 'Time off request '.$decision,
            body: "Your time off request for {$request->start_date->toDateString()} - {$request->end_date->toDateString()} was {$decision}.",
            relatedEmploymentId: $request->employment_id,
        );
    }

    public function recalculateBalance(int $employmentId, int $policyId): TimeOffBalance
    {
        $total = TimeOffLedgerEntry::where('employment_id', $employmentId)
            ->where('policy_id', $policyId)
            ->sum('hours');

        return TimeOffBalance::updateOrCreate(
            ['employment_id' => $employmentId, 'policy_id' => $policyId],
            ['balance_hours' => $total, 'as_of_date' => now()->toDateString()],
        );
    }
}
