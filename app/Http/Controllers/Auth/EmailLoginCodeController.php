<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmailLoginCodeController extends Controller
{
    /**
     * Display the "request a code" view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/LoginWithCode', [
            'status' => session('status'),
        ]);
    }

    /**
     * Generate and email a sign-in code for the given stakeholder account.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        $throttleKey = 'login-code-request:'.Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        $user = User::query()
            ->where('email', $request->input('email'))
            ->where('user_type', UserType::External)
            ->first();

        if (! $user) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => "We can't find a stakeholder account with that email address.",
            ]);
        }

        RateLimiter::hit($throttleKey, 300);

        $code = (string) random_int(100000, 999999);

        $user->loginCodes()->whereNull('consumed_at')->delete();
        $user->loginCodes()->create([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new LoginCodeNotification($code));

        return to_route('login.code.verify', ['email' => $user->email])
            ->with('status', 'We emailed you a 6-digit code. It expires in 10 minutes.');
    }

    /**
     * Display the "enter your code" view.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Auth/VerifyLoginCode', [
            'email' => $request->query('email', ''),
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the submitted code and log the stakeholder in.
     *
     * @throws ValidationException
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string'],
        ]);

        $throttleKey = 'login-code-verify:'.Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'code' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        $user = User::query()
            ->where('email', $request->input('email'))
            ->where('user_type', UserType::External)
            ->first();

        $loginCode = $user?->loginCodes()->whereNull('consumed_at')->latest()->first();

        if (! $user || ! $loginCode || $loginCode->isExpired() || ! Hash::check($request->input('code'), $loginCode->code_hash)) {
            RateLimiter::hit($throttleKey);
            $loginCode?->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'That code is incorrect or has expired.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $loginCode->update(['consumed_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard', absolute: false));
    }
}
