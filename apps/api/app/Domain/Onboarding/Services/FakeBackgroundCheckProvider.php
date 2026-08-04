<?php

namespace App\Domain\Onboarding\Services;

use Illuminate\Support\Str;

/**
 * Local stand-in for Checkr (background_check) / the federal E-Verify API
 * (e_verify). Resolves instantly to "clear" — a real integration would
 * start "pending" and resolve later via webhook. Bound in
 * AppServiceProvider; swapping in a real provider is a single binding change.
 */
class FakeBackgroundCheckProvider implements BackgroundCheckProviderInterface
{
    public function runCheck(string $checkType, string $subjectName, string $subjectEmail): BackgroundCheckResult
    {
        return new BackgroundCheckResult(
            provider: 'fake',
            referenceId: 'fake_chk_'.Str::random(24),
            status: 'clear',
        );
    }
}
