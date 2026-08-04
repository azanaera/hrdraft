<?php

namespace App\Domain\Compensation\Services;

interface BankingProviderInterface
{
    /**
     * Exchanges raw (transient, never-persisted) account/routing numbers for
     * a provider token + display-safe metadata. A real implementation would
     * call Plaid/Stripe Treasury/an embedded payroll provider's API here.
     */
    public function tokenize(string $routingNumber, string $accountNumber, string $accountType): BankingTokenResult;
}
