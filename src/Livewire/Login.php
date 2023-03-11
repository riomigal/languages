<?php

namespace Riomigal\Languages\Livewire;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Redirector;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translator;

class Login extends Component
{
    use WithRateLimiting;

    /**
     * @var string
     */
    public string $email;

    /**
     * @var string
     */
    public string $password;

    /**
     * @var bool
     */
    public bool $remember = false;


    /**
     * @return string[]
     */
    public function getRules(): array
    {
        return [
            'email' => 'string|email|required',
            'password' => 'string',
            'remember' => 'boolean'
        ];
    }


    /**
     * @return void
     */
    public function mount(): void
    {
        if (auth()->check()) {
            redirect(route('languages.languages'));
        };
    }

    /**
     * @return Redirector|null
     */
    public function login(): Redirector|null
    {
        Auth::shouldUse(config('languages.translator_guard'));
        try {
            $this->rateLimit(10);
        } catch (TooManyRequestsException $exception) {
            $this->addError('email', "Slow down! Please wait another {$exception->secondsUntilAvailable} seconds to log in.",);
            return null;
        }

        $this->validate();

        if (!auth()->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ])) {
            $this->addError('email', 'Email or password are invalid.');
            return null;
        }

        auth()->login(Translator::query()->where('email', $this->email)->first(), $this->remember);

        return redirect(route('languages.languages'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::login',
            ['data' => Language::query()->paginate(1)])
            ->layout('languages::layouts.app');
    }
}
