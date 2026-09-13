<?php

namespace Riomigal\Languages\Livewire;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Livewire\Features\SupportRedirects\Redirector as LivewireRedirector;
use Livewire\Component;
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
        if (auth($this->translatorGuard())->check()) {
            redirect(route('languages.languages'));
        };
    }

    /**
     * Livewire 3's redirect() returns Livewire\Features\SupportRedirects\Redirector,
     * not an Illuminate RedirectResponse. Declaring the narrower Illuminate type
     * made every successful login throw a TypeError, so the application could not
     * be logged into at all.
     *
     * @return RedirectResponse|Redirector|LivewireRedirector|null
     */
    public function login(): RedirectResponse|Redirector|LivewireRedirector|null
    {
        try {
            $this->rateLimit(10);
        } catch (TooManyRequestsException $exception) {
            $secondsUntilAvailable = $exception->secondsUntilAvailable;
            if (!is_int($secondsUntilAvailable)) {
                throw new \TypeError('The rate limiter retry delay must be an integer.');
            }
            $this->addError('email', "Slow down! Please wait another {$secondsUntilAvailable} seconds to log in.",);
            return null;
        }

        $this->validate();

        if (!auth($this->translatorGuard())->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ])) {
            $this->addError('email', 'Email or password are invalid.');
            return null;
        }

        $guard = auth($this->translatorGuard());
        $translator = Translator::where('email', $this->email)->first();
        if ($translator === null) {
            throw new \TypeError('The authenticated translator must exist.');
        }
        $guard->login($translator, $this->remember);

        return redirect(route('languages.languages'));
    }

    private function translatorGuard(): ?string
    {
        $guard = config('languages.translator_guard');
        if ($guard !== null && !is_string($guard)) {
            throw new \TypeError('languages.translator_guard must be a string or null.');
        }

        return $guard;
    }

    /**
     * @return View
     */
    public function render(): View
    {
        /** @phpstan-var view-string $viewName */
        $viewName = 'languages::login';
        $view = view($viewName,
            ['data' => Language::query()->paginate(1)]);
        $view->layout('languages::layouts.app');

        return $view;
    }
}
