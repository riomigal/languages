<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithPagination;
use Riomigal\Languages\Models\Translator;

abstract class AuthComponent extends Component
{
    use WithPagination;

    /**
     * @var string
     */
    public string $search = '';

    /**
     * @var bool
     */
    public ?bool $isAdministrator = false;

    /**
     * @var array<int|string, string|bool|null> Property names and three-state filter values.
     */
    protected $queryString = ['search', 'page'];

    /**
     * The mounted component always has a translator; init() aborts for guests.
     */
    public Translator $authUser;

    /**
     * @return LengthAwarePaginator<int, covariant Model>|Model
     */
    abstract public function query(): LengthAwarePaginator|Model;

    /**
     * @return void
     */
    public function init(): void
    {
        $guard = config('languages.translator_guard');
        if ($guard !== null && !is_string($guard)) {
            throw new \TypeError('languages.translator_guard must be a string or null.');
        }
        $authUser = Translator::find(auth($guard)->user()?->id);
        if (!$authUser) abort(403);
        $this->authUser = $authUser;
        $this->isAdministrator = $this->authUser->admin;
    }


    /**
     * @return void
     */
    public function updatedSearch(): void
    {
        $this->gotoPage(1);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if (!$this->isAdministrator) {
            $this->showNoAuthorizationMessage();
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function update(): bool
    {
        if (!$this->isAdministrator) {
            $this->showNoAuthorizationMessage();
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        if (!$this->isAdministrator) {
            $this->showNoAuthorizationMessage();
            return false;
        }
        return true;
    }

    /**
     * @return void
     */
    public function showNoAuthorizationMessage(): void
    {
        $this->dispatch('showToast', message: 'Action not authorized!', type: LanguagesToastMessage::MESSAGE_TYPES['WARNING'], duration: 3000);
    }

    /**
     * @return void
     */
    public function handleException(): void {
        $this->dispatch('showToast', message: __('languages::global.something_wrong'), type: LanguagesToastMessage::MESSAGE_TYPES['WARNING'], duration: 4000);
    }
}
