<?php

namespace App\Domain\Compensation\Services;

use Illuminate\Support\Str;

/**
 * Local stand-in for a real tokenizing provider (Plaid, Stripe Treasury, an
 * embedded payroll API). Simulates an instant "verified" round trip. Bound
 * in AppServiceProvider — swapping in a real provider later is a single
 * binding change, not a rewrite of anything that calls this interface.
 */
class FakeBankingProvider implements BankingProviderInterface
{
    public function tokenize(string $routingNumber, string $accountNumber, string $accountType): BankingTokenResult
    {
        return new BankingTokenResult(
            provider: 'fake',
            token: 'fake_tok_'.Str::random(24),
            accountLastFour: substr($accountNumber, -4),
            verified: true,
        );
    }
}
