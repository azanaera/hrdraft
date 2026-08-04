<?php

namespace App\Domain\Onboarding\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Onboarding\Models\BackgroundCheck;
use App\Domain\Timeline\Services\TimelineRecorder;

class BackgroundCheckService
{
    public function __construct(
        private readonly BackgroundCheckProviderInterface $provider,
        private readonly TimelineRecorder $timeline,
    ) {
    }

    public function run(Employment $employment, string $checkType): BackgroundCheck
    {
        $result = $this->provider->runCheck(
            $checkType,
            $employment->person->fullName(),
            $employment->person->personal_email ?? '',
        );

        $check = BackgroundCheck::updateOrCreate(
            ['employment_id' => $employment->id, 'check_type' => $checkType],
            [
                'provider' => $result->provider,
                'external_reference_id' => $result->referenceId,
                'status' => $result->status,
                'resolved_at' => $result->status !== 'pending' ? now() : null,
            ],
        );

        $this->timeline->record(
            person: $employment->person,
            employment: $employment,
            eventType: 'background_check_updated',
            summary: ucfirst(str_replace('_', ' ', $checkType))." status: {$result->status}.",
            payload: ['check_type' => $checkType, 'status' => $result->status],
            visibility: 'admin_only',
        );

        return $check;
    }
}
