<?php

namespace Riomigal\Languages\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Riomigal\Languages\Requests\LoginRequest;

class LoginController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (Auth::guard($this->guard())->check()) {
            return redirect()->route('languages.languages');
        }

        return view('languages::login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        // An IP-scoped limit also prevents bypassing the limit by changing email.
        $key = 'languages:login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Slow down! Please wait another {$seconds} seconds to log in.",
            ]);
        }
        RateLimiter::hit($key, 60);

        if (!Auth::guard($this->guard())->attempt($request->safe()->only(['email', 'password']), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email or password are invalid.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->route('languages.languages');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard($this->guard())->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('languages.login');
    }

    private function guard(): string
    {
        /** @var string $guard Configured session guard. */
        $guard = config('languages.translator_guard');
        return $guard;
    }
}
