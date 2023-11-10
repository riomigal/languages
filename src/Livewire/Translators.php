<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translator;

class Translators extends AuthComponent
{
    /**
     * @var bool
     */
    public bool $showForm = false;

    /**
     * @var array
     */
    public array $availableLanguages = [];

    /**
     * @var string
     */
    public string $email = '';

    /**
     * @var string|null
     */
    public string|null $phone = '';

    /**
     * @var string
     */
    public string $first_name = '';

    /**
     * @var string
     */
    public string $last_name = '';

    /**
     * @var bool
     */
    public bool $admin = false;

    /**
     * @var string
     */
    public string $password = '';

    /**
     * @var string
     */
    public string $password_confirmation = '';

    /**
     * @var array
     */
    public array $languages = [];


    /**
     * @var array
     */
    public array $selectedLanguages = [];

    /**
     * @var Translator|null
     */
    public Translator|null $translator = null;

    /**
     * @var string[]
     */
    protected $queryString = ['search', 'page', 'selectedLanguages'];

    /**
     * @var bool
     */
    public bool $showUpdatePasswordForm = false;

    /**
     * @var string
     */
    public string $new_password = '';

    /**
     * @var string
     */
    public string $new_password_confirmation = '';


    /**
     * @return array
     */
    public function getRules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique(config('languages.table_translators'), 'email')->ignore(($this->translator) ? $this->translator->id : 0)],
            'phone' => ['nullable', 'string', Rule::unique(config('languages.table_translators'), 'phone')->ignore(($this->translator) ? $this->translator->id : 0)],
            'admin' => 'nullable|bool',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
            'languages' => 'required|array'
        ];
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        parent::init();
        if (!$this->isAdministrator) {
            abort(403);
        }
        $this->availableLanguages = Language::query()->get()->all();
    }

    /**
     * @param int|null $id
     * @return void
     */
    public function showForm(int|null $id = null): void
    {
        $this->resetErrorBag();
        $this->showForm = true;
        if ($id) {
            $this->translator = Translator::query()->findOrFail($id);
            $this->email = $this->translator->email;
            $this->phone = $this->translator->phone;
            $this->first_name = $this->translator->first_name;
            $this->last_name = $this->translator->last_name;
            $this->languages = $this->translator->languages()->get()->pluck('id')->all();
            $this->admin = $this->translator->admin;
        } else {
            $this->reset(['email', 'phone', 'password', 'password_confirmation', 'first_name', 'last_name', 'admin', 'languages']);
            $this->translator = null;
        }
    }

    /**
     * @return void
     */
    public function closeForm(): void
    {
        $this->reset(['email', 'phone', 'password', 'password_confirmation', 'first_name', 'last_name', 'admin', 'languages', 'showForm']);
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        if (parent::create()) {
            $this->validate();
            $translator = Translator::query()->create(
                [
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'password' => Hash::make($this->password),
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'admin' => $this->admin
                ]
            );
            $translator->languages()->detach();
            $translator->languages()->attach($this->languages);
            $this->emit('showToast', __('languages::translators.created'), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS']);
            $this->reset(['email', 'phone', 'password', 'password_confirmation', 'first_name', 'last_name', 'admin', 'languages', 'showForm']);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function update(): bool
    {
        if (parent::update()) {
            $this->password = 'aaaaaaaa';
            $this->password_confirmation = 'aaaaaaaa';
            $this->validate();
            $this->translator->update(
                [
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'admin' => $this->admin
                ]
            );
            $this->translator->languages()->detach();
            $this->translator->languages()->attach($this->languages);
            $this->emit('showToast', __('languages::translators.updated'), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS']);
            $this->reset(['email', 'phone', 'password', 'password_confirmation', 'first_name', 'last_name', 'admin', 'languages', 'showForm']);
            return true;
        }
        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if ($id == 1) return false; // Prevents super admin from deleting own account
        if (parent::delete($id)) {
            $translator = Translator::query()->findOrFail($id);
            $translator->delete();
            $this->emit('showToast', __('languages::translators.deleted'), LanguagesToastMessage::MESSAGE_TYPES['WARNING']);
            return true;
        }
        return false;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function query(): LengthAwarePaginator
    {
        return Translator::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where(
                        'first_name', 'LIKE', '%' . $this->search . '%'
                    )
                        ->orWhere('last_name', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('email', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('phone', 'LIKE', '%' . $this->search . '%')
                        ->orWhere(config('languages.table_translators') . '.id', 'LIKE', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedLanguages, function ($query) {
                foreach ($this->selectedLanguages as $language) {
                    $query->where(function ($query) use ($language) {
                        $query->whereHas('languages', function ($query) use ($language) {
                            $query->where(function ($query) use ($language) {
                                $query->where('languages.id', $language);
                            });
                        });
                    });
                }
            })
            ->with('languages')->paginate(10);
    }

    /**
     * @return void
     */
    public function toggleUpdatePasswordForm(): void
    {
        $this->showUpdatePasswordForm = !$this->showUpdatePasswordForm;
    }


    /**
     * @return void
     */
    public function updateNewPassword(): void
    {
        $this->validate([
            'new_password' => 'required|min:8',
            'new_password_confirmation' => 'required|same:new_password',
        ]);
        $this->translator->password = Hash::make($this->new_password);
        $this->translator->save();
        $this->toggleUpdatePasswordForm();
        $this->emit('showToast', __('languages::translators.password_updated_success', ['email'=> $this->translator->email]), LanguagesToastMessage::MESSAGE_TYPES['SUCCESS']);
    }


    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::translators',
            [
                'data' => $this->query(),
                'availableLanguages' => $this->availableLanguages
            ]
        )
            ->layout('languages::layouts.app');
    }
}
