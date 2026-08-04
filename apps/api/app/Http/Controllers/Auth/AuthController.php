<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Web SPA login: establishes a session (Sanctum stateful cookie auth).
     * The SPA must first GET /sanctum/csrf-cookie.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->assertNotRateLimited($request, $credentials['email']);

        if (! Auth::attempt($credentials, remember: true)) {
            RateLimiter::hit($this->throttleKey($request, $credentials['email']), 60);

            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $credentials['email']));

        $this->rejectIfTerminated($request);

        $request->session()->regenerate();

        return response()->json(['data' => $this->serializeUser($request->user())]);
    }

    /**
     * Mobile login: returns a bearer token (Sanctum personal access token)
     * since React Native doesn't share cookies with the API origin.
     */
    public function mobileLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        $this->assertNotRateLimited($request, $credentials['email']);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request, $credentials['email']), 60);

            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $credentials['email']));

        if ($user->employment && $user->employment->employment_status === 'terminated') {
            throw ValidationException::withMessages([
                'email' => ['This account no longer has access.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'])->plainTextToken;

        return response()->json([
            'data' => $this->serializeUser($user),
            'token' => $token,
        ]);
    }

    /**
     * Only failed attempts count against the throttle — a legitimate user
     * (or client) logging in successfully several times in a minute (across
     * tabs/devices, or an automated test suite exercising the app) must
     * never get locked out; only repeated wrong passwords should.
     */
    private function assertNotRateLimited(Request $request, string $email): void
    {
        $key = $this->throttleKey($request, $email);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            abort(429, "Too many login attempts. Please try again in {$seconds} seconds.");
        }
    }

    private function throttleKey(Request $request, string $email): string
    {
        return 'login:'.strtolower($email).'|'.$request->ip();
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // Always respond the same way regardless of whether the email exists,
        // so this endpoint can't be used to enumerate registered accounts.
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'If that email is registered, a reset link has been sent.']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }

    private function rejectIfTerminated(Request $request): void
    {
        $user = $request->user();

        if ($user && $user->employment && $user->employment->employment_status === 'terminated') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => ['This account no longer has access.'],
            ]);
        }
    }

    public function logout(Request $request)
    {
        // Web/cookie-session auth resolves to a Sanctum TransientToken, which
        // has no delete() method (there's no real DB-backed token to revoke —
        // the session itself is what gets invalidated below). Only a real
        // bearer-token (mobile) request has an actual PersonalAccessToken.
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        Auth::guard('web')->logout();

        // A bearer-token (mobile) request never gets session middleware
        // attached in the first place (EnsureFrontendRequestsAreStateful only
        // adds it for whitelisted stateful-domain origins) — $request->session()
        // throws in that case rather than returning null, so hasSession() has
        // to be checked first.
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->serializeUser($request->user())]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'employment_id' => $user->employment_id,
        ];
    }
}
