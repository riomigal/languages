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
     * Validate just the selected field, using its name as the input key.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $field = $this->field();

        return [$field => self::FIELD_RULES[$field]];
    }

    /**
     * Supply the saved multi-host flag as context, never as another autosaved field.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $field = $this->field();
        $data = $this->only($field);

        if ($field === 'domains') {
            $setting = Setting::query()->first();
            $data['enable_multi_host'] = (bool) ($setting?->getAttributes()['enable_multi_host'] ?? false);
        }

        return $data;
    }
}
