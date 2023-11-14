<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Riomigal\Languages\Models\Setting;

class Settings extends AuthComponent
{
    /**
     * @var Setting
     */
    public Setting $setting;

    /**
     * @return array
     */
    public function getRules(): array
    {
        return [
            'setting.db_loader' => 'boolean',
            'setting.import_vendor' => 'boolean',
            'setting.enable_pending_notifications' => 'boolean',
            'setting.enable_automatic_pending_notifications' => 'boolean',
            'setting.enable_open_ai_translations' => 'boolean',
        ];
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        parent::init();
        $this->setting = $this->query();
        if (!$this->isAdministrator) {
            abort(403);
        }
    }


    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function updated($key, $value): void
    {
        $this->validate();
        $this->setting->{str_replace('setting.', '',$key)} = $value;
        $this->setting->save();
        Setting::getFreshCached();
    }

    /**
     * @return Model
     */
    public function query(): Model
    {
        return Setting::getCached();
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('languages::settings',
            [
                'data' => $this->query(),
            ]
        )
            ->layout('languages::layouts.app');
    }
}
