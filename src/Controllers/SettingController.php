<?php

namespace Riomigal\Languages\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Requests\UpdateSettingFieldRequest;
use Riomigal\Languages\Services\Toast;

class SettingController extends BaseController
{
    public function index(): View
    {
        return view('languages::settings', ['setting' => Setting::getCached()]);
    }

    public function update(UpdateSettingFieldRequest $request): RedirectResponse
    {
        $field = $request->field();
        $setting = Setting::query()->firstOrFail();
        $setting->setAttribute($field, $request->validated($field));
        $setting->save();
        Setting::getFreshCached();
        Toast::flash('Setting saved.');
        return redirect()->route('languages.settings');
    }
}
