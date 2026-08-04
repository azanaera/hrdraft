<?php

namespace App\Domain\Compensation\Services;

class BankingTokenResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $token,
        public readonly string $accountLastFour,
        public readonly bool $verified,
    ) {
    }
}
