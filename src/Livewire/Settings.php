<?php

namespace Riomigal\Languages\Livewire;

use Illuminate\Contracts\View\View;
use Riomigal\Languages\Models\Setting;

class Settings extends AuthComponent
{
    /**
     * @var array<string, mixed>
     */
    public array $setting = [];

    /**
     * @return array<string, string>
     */
    public function getRules(): array
    {
        return [
            'setting.domains' => 'nullable|required_if:setting.enable_multi_host,true',
            'setting.enable_multi_host' => 'boolean',
            'setting.db_loader' => 'boolean',
            'setting.import_vendor' => 'boolean',
            'setting.enable_pending_notifications' => 'boolean',
            'setting.enable_automatic_pending_notifications' => 'boolean',
            'setting.enable_open_ai_translations' => 'boolean',
            'setting.import_only_from_root_language' => 'boolean',
            'setting.allow_deleting_languages' => 'boolean',
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

        $setting = $this->query();
        foreach (array_keys($this->getRules()) as $key) {
            $field = substr($key, strlen('setting.'));
            $this->setting[$field] = $setting->getAttribute($field);
        }
    }


    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function updated(string $key, mixed $value): void
    {
        if (!array_key_exists($key, $this->getRules())) {
            abort(422);
        }

        $this->validateOnly($key);
        if ($key === 'setting.enable_multi_host') {
            $this->validateOnly('setting.domains');
        }
        $setting = $this->query();
        $setting->setAttribute(substr($key, strlen('setting.')), $value);
        $setting->save();
        Setting::getFreshCached();
    }

    /**
     * @return Setting
     */
    public function query(): Setting
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
