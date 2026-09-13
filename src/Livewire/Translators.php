<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\PendingTranslationsNotification;

class Translators extends AuthComponent
{
    /**
     * @var bool
     */
    public bool $showForm = false;

    /**
     * @var array<int, Language>
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
     * @var array<int, int|string>
     */
    public array $languages = [];


    /**
     * @var array<int, int|string>
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
     * @return array<string, string|list<string|Unique>>
     */
    public function getRules(): array
    {
        $connection = config('languages.db_connection');
        $table = config('languages.table_translators');
        if ($connection !== null && !is_string($connection)) {
            throw new \TypeError('languages.db_connection must be a string or null for validation.');
        }
        if ($table !== null && !is_string($table)) {
            throw new \TypeError('languages.table_translators must be a string or null.');
        }
        $rules = [
            'email' => ['required', 'email', Rule::unique($connection . '.' . $table, 'email')->ignore(($this->translator) ? $this->translator->id : 0)],
            'phone' => ['nullable', 'string', Rule::unique($connection . '.' . $table, 'phone')->ignore(($this->translator) ? $this->translator->id : 0)],
            'admin' => 'nullable|bool',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ];

        if ($this->admin === false) {
            $rules['languages'] = 'required|array';
        } else {
            $rules['languages'] = 'nullable|array';
        }

        return $rules;
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
            /** @var list<int> $languages Assigned language primary keys. */
            $languages = $this->translator->languages()->get()->pluck('id')->all();
            $this->languages = $languages;
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
            $this->dispatch('showToast', message: __('languages::translators.created'), type: LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], duration: 3000);
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
            if ($this->translator === null) {
                throw new \Error('Call to a member function update() on null');
            }
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
            $this->dispatch('showToast', message: __('languages::translators.updated'), type: LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], duration: 3000);
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
            $this->dispatch('showToast', message: __('languages::translators.deleted'), type: LanguagesToastMessage::MESSAGE_TYPES['WARNING'], duration: 3000);
            return true;
        }
        return false;
    }

    /**
     * @return LengthAwarePaginator<int, Translator>
     */
    public function query(): LengthAwarePaginator
    {
        return Translator::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $table = config('languages.table_translators');
                    if ($table !== null && !is_string($table)) {
                        throw new \TypeError('languages.table_translators must be a string or null.');
                    }
                    $query->where(
                        'first_name', 'LIKE', '%' . $this->search . '%'
                    )
                        ->orWhere('last_name', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('email', 'LIKE', '%' . $this->search . '%')
                        ->orWhere('phone', 'LIKE', '%' . $this->search . '%')
                        ->orWhere($table . '.id', 'LIKE', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedLanguages, function ($query) {
                foreach ($this->selectedLanguages as $language) {
                    $query->where(function ($query) use ($language) {
                        $query->whereHas('languages', function ($query) use ($language) {
                            $query->where(function ($query) use ($language) {
                                $table = config('languages.table_languages');
                                if ($table !== null && !is_string($table)) {
                                    throw new \TypeError('languages.table_languages must be a string or null.');
                                }
                                $query->where($table . '.id', $language);
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
        $translator = $this->translator;
        if ($translator === null) {
            throw new \Error('Attempt to assign property "password" on null');
        }
        $translator->password = Hash::make($this->new_password);
        $translator->save();
        $this->toggleUpdatePasswordForm();
        $this->dispatch('showToast', message: __('languages::translators.password_updated_success', ['email'=> $translator->email]), type: LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], duration: 3000);
    }

    public function notifyPendingTranslations(): void
    {
        $translator = $this->translator;
        if ($translator === null) {
            throw new \Error('Call to a member function languages() on null');
        }
        $translator->languages()->each(function(Language $language) use ($translator) {
            $translator->notify(new PendingTranslationsNotification($language));
        });
        $this->dispatch('showToast', message: __('languages::pending-translations-notification.success', ['email' => $translator->email]), type: LanguagesToastMessage::MESSAGE_TYPES['SUCCESS'], duration: 3000);
    }


    /**
     * @return View
     */
    public function render(): View
    {
        /** @phpstan-var view-string $viewName */
        $viewName = 'languages::translators';
        $view = view($viewName,
            [
                'data' => $this->query(),
                'availableLanguages' => $this->availableLanguages
            ]
        );
        $view->layout('languages::layouts.app');

        return $view;
    }
}
