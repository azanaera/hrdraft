<?php

namespace App\Domain\Onboarding\Services;

class BackgroundCheckResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $referenceId,
        public readonly string $status,
    ) {
    }
}
