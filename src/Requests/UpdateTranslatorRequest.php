<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Riomigal\Languages\Models\Translator;

class UpdateTranslatorRequest extends FormRequest
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
     * Ignore only the translator bound to the {translator} route parameter.
     *
     * @return array
     */
    public function rules(): array
    {
        $translator = $this->route('translator');

        if (!$translator instanceof Translator) {
            abort(404);
        }

        return [
            'email' => ['required', 'email', Rule::unique(config('languages.db_connection') . '.' . config('languages.table_translators'), 'email')->ignore($translator)],
            'phone' => ['nullable', 'string', Rule::unique(config('languages.db_connection') . '.' . config('languages.table_translators'), 'phone')->ignore($translator)],
            'admin' => 'nullable|bool',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'languages' => $this->boolean('admin') ? 'nullable|array' : 'required|array',
        ];
    }

    /**
     * Return profile attributes only, leaving the existing password untouched.
     *
     * @return array<string, mixed>
     */
    public function translatorAttributes(): array
    {
        return $this->safe()->only(['email', 'phone', 'first_name', 'last_name', 'admin']);
    }
}
