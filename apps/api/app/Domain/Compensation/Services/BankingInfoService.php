<?php

namespace App\Domain\Compensation\Services;

use App\Domain\Employee\Models\Employment;
use App\Domain\Compensation\Models\EmploymentBankingInfo;
use App\Domain\Timeline\Services\TimelineRecorder;

class BankingInfoService
{
    public function __construct(
        private readonly BankingProviderInterface $provider,
        private readonly TimelineRecorder $timeline,
    ) {
    }

    /**
     * Routing/account numbers exist only as method parameters here — never
     * assigned to a model, never logged, discarded the instant tokenize()
     * returns.
     */
    public function capture(Employment $employment, string $routingNumber, string $accountNumber, string $accountType): EmploymentBankingInfo
    {
        $result = $this->provider->tokenize($routingNumber, $accountNumber, $accountType);

        $info = EmploymentBankingInfo::updateOrCreate(
            ['employment_id' => $employment->id],
            [
                'provider' => $result->provider,
                'external_token' => $result->token,
                'account_last_four' => $result->accountLastFour,
                'account_type' => $accountType,
                'verified_at' => $result->verified ? now() : null,
            ],
        );

        $this->timeline->record(
            person: $employment->person,
            employment: $employment,
            eventType: 'banking_info_updated',
            summary: "Direct deposit account updated (ending in {$result->accountLastFour}).",
            visibility: 'admin_only',
        );

        return $info;
    }
}
