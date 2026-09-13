<?php

namespace Riomigal\Languages\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Notifications\PendingTranslationsNotification;
use Riomigal\Languages\Requests\StoreTranslatorRequest;
use Riomigal\Languages\Requests\UpdateTranslatorPasswordRequest;
use Riomigal\Languages\Requests\UpdateTranslatorRequest;
use Riomigal\Languages\Services\Toast;

class TranslatorController extends BaseController
{
    public function index(Request $request, ?Translator $translator = null): View
    {
        $request->validate(['search' => 'nullable|string', 'selectedLanguages' => 'sometimes|array', 'selectedLanguages.*' => 'integer']);
        $search = $request->string('search')->toString();
        $query = Translator::query()->with('languages');
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                foreach (['first_name', 'last_name', 'email', 'phone', 'id'] as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        /** @var list<int|string> $selectedLanguages Validated language IDs. */
        $selectedLanguages = $request->input('selectedLanguages', []);
        foreach ($selectedLanguages as $id) {
            $query->whereHas('languages', fn ($query) => $query->whereKey($id));
        }
        return view('languages::translators', [
            'data' => $query->orderBy('id')->paginate(10)->withQueryString(),
            'availableLanguages' => Language::query()->orderBy('id')->get(),
            'translator' => $translator,
            'showForm' => $translator !== null || $request->boolean('create'),
            'showUpdatePasswordForm' => $translator !== null && $request->boolean('password'),
            'search' => $search,
            'selectedLanguages' => $selectedLanguages,
        ]);
    }

    public function store(StoreTranslatorRequest $request): RedirectResponse
    {
        $translator = new Translator();
        $translator->getConnection()->transaction(function () use ($request, $translator): void {
            $translator->fill($request->translatorAttributes())->save();
            /** @var list<int|string> $languages Validated assignment IDs. */
            $languages = $request->validated('languages', []) ?? [];
            $translator->languages()->sync($languages);
        });
        Toast::flash(__('languages::translators.created'));
        return redirect()->route('languages.translators');
    }

    public function update(UpdateTranslatorRequest $request, Translator $translator): RedirectResponse
    {
        $translator->getConnection()->transaction(function () use ($request, $translator): void {
            $translator->update($request->translatorAttributes());
            /** @var list<int|string> $languages Validated assignment IDs. */
            $languages = $request->validated('languages', []) ?? [];
            $translator->languages()->sync($languages);
        });
        Toast::flash(__('languages::translators.updated'));
        return redirect()->route('languages.translators');
    }

    public function delete(Translator $translator): RedirectResponse
    {
        abort_if($translator->id === 1, 403);
        $translator->getConnection()->transaction(function () use ($translator): void {
            $translator->languages()->detach();
            $translator->delete();
        });
        Toast::flash(__('languages::translators.deleted'), 'DELETED');
        return redirect()->route('languages.translators');
    }

    public function updateNewPassword(UpdateTranslatorPasswordRequest $request, Translator $translator): RedirectResponse
    {
        $translator->update($request->translatorAttributes());
        Toast::flash(__('languages::translators.password_updated_success', ['email' => $translator->email]));
        return redirect()->route('languages.translators.edit', $translator);
    }

    public function notifyPendingTranslations(Translator $translator): RedirectResponse
    {
        abort_unless(Setting::getCached()->enable_pending_notifications, 403);
        $translator->languages()->each(function (Language $language) use ($translator): void {
            $translator->notify(new PendingTranslationsNotification($language));
        });
        Toast::flash(__('languages::pending-translations-notification.success', ['email' => $translator->email]));
        return redirect()->route('languages.translators.edit', $translator);
    }
}
