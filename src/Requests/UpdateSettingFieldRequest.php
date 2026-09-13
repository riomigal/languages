<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Riomigal\Languages\Models\Setting;
use Riomigal\Languages\Models\Translator;

class UpdateSettingFieldRequest extends FormRequest
{
    /**
     * @var array<string, string>
     */
    private const FIELD_RULES = [
        'domains' => 'nullable|required_if:enable_multi_host,true',
        'enable_multi_host' => 'boolean',
        'db_loader' => 'boolean',
        'import_vendor' => 'boolean',
        'enable_pending_notifications' => 'boolean',
        'enable_automatic_pending_notifications' => 'boolean',
        'enable_open_ai_translations' => 'boolean',
        'import_only_from_root_language' => 'boolean',
        'allow_deleting_languages' => 'boolean',
    ];

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        $authUser = $this->attributes->get('authUser');

        return $authUser instanceof Translator && $authUser->admin;
    }

    /**
     * Return the supported setting named by the {field} route parameter.
     *
     * @return string
     */
    public function field(): string
    {
        $field = $this->route('field');

        if (!is_string($field) || !array_key_exists($field, self::FIELD_RULES)) {
            abort(404);
        }

        return $field;
    }

    /**
     * Validate the selected field and require saved domains when enabling multi-host.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $field = $this->field();

        $rules = [$field => self::FIELD_RULES[$field]];

        if ($field === 'enable_multi_host') {
            $rules['domains'] = self::FIELD_RULES['domains'];
        }

        return $rules;
    }

    /**
     * Use saved values for the other field so submitted context cannot bypass validation.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $field = $this->field();
        /** @var array<string, mixed> $data Unvalidated input under a supported, non-numeric field name. */
        $data = $this->only($field);

        if ($field === 'domains' || $field === 'enable_multi_host') {
            $setting = Setting::query()->first();
            if ($field === 'domains') {
                $data['enable_multi_host'] = $setting->enable_multi_host ?? false;
            } else {
                $data['domains'] = $setting?->domains;
            }
        }

        return $data;
    }
}
