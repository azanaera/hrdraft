<?php

namespace App\Domain\Onboarding\Services;

interface BackgroundCheckProviderInterface
{
    /**
     * Initiates a background check or E-Verify (I-9 employment eligibility)
     * run. A real implementation would call Checkr / the federal E-Verify
     * API here and the result would usually arrive later via webhook — this
     * interface's contract already accounts for a "pending" status for that
     * reason, even though the fake implementation resolves instantly.
     */
    public function runCheck(string $checkType, string $subjectName, string $subjectEmail): BackgroundCheckResult;
}
