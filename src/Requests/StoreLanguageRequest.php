<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translator;

class StoreLanguageRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        $authUser = $this->attributes->get('authUser');

        return $authUser instanceof Translator && $authUser->admin;
    }

    /**
     * Validate language codes using the existing creation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'language' => [
                Rule::in(array_column(Language::LANGUAGES, 'code')),
                Rule::notIn(Language::query()->pluck('code')->all()),
                Rule::unique(config('languages.db_connection') . '.' . config('languages.table_languages'), 'code'),
            ],
        ];
    }
}
