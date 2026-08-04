<?php

namespace App\Providers;

use App\Domain\Compensation\Services\BankingProviderInterface;
use App\Domain\Compensation\Services\FakeBankingProvider;
use App\Domain\Employee\Models\Employment;
use App\Domain\Onboarding\Services\BackgroundCheckProviderInterface;
use App\Domain\Onboarding\Services\FakeBackgroundCheckProvider;
use App\Domain\Documents\Services\FakeSignatureProvider;
use App\Domain\Documents\Services\SignatureProviderInterface;
use App\Policies\EmployeePolicy;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Every third-party integration in this app (banking tokenization,
     * e-signatures, background checks) is bound to a Fake*Provider here.
     * Swapping in a real SDK later (Plaid, DocuSign, Checkr) is a one-line
     * change to these bindings — nothing that calls the interface changes.
     */
    public function register(): void
    {
        $this->app->bind(BankingProviderInterface::class, FakeBankingProvider::class);
        $this->app->bind(SignatureProviderInterface::class, FakeSignatureProvider::class);
        $this->app->bind(BackgroundCheckProviderInterface::class, FakeBackgroundCheckProvider::class);
    }

    public function boot(): void
    {
        Gate::policy(Employment::class, EmployeePolicy::class);

        // Models live under nested App\Domain\* namespaces but factories are
        // flat in Database\Factories, so map by class basename instead of
        // Laravel's default (namespace-mirroring) guess.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // This is a JSON-only API with no "login" view/route. Without this,
        // an unauthenticated request that doesn't send Accept: application/json
        // makes the Authenticate middleware try to redirect to a named
        // "login" route that doesn't exist, throwing a 500 instead of 401.
        Authenticate::redirectUsing(fn () => null);

        // 5 attempts/minute per email+IP — cheap brute-force insurance on login.
        RateLimiter::for('login', function ($request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again in a minute.',
                ], 429);
            });
        });
    }
}
