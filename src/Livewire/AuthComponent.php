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
     * @var string[]
     */
    protected $queryString = ['search', 'page'];

    /**
     * @return void
     */
    public ?Translator $authUser;

    /**
     * @return LengthAwarePaginator|Model
     */
    abstract public function query(): LengthAwarePaginator|Model;

    /**
     * @return void
     */
    public function init(): void
    {
        $this->authUser = Translator::find(auth(config('languages.translator_guard'))->user()?->id);
        $this->isAdministrator = $this->authUser?->admin;
    }


    /**
     * @return void
     */
    protected function updatedSearch(): void
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
        $this->emit('showToast', 'Action not authorized!', LanguagesToastMessage::MESSAGE_TYPES['WARNING']);
    }

    /**
     * @return void
     */
    public function handleException(): void {
        $this->emit('showToast', __('languages::global.something_wrong'), LanguagesToastMessage::MESSAGE_TYPES['WARNING'], 4000);
    }
}
