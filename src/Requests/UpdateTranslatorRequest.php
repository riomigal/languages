<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Rules\Exists;
use Riomigal\Languages\Models\Language;
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
     * @return array<string, string|list<string|Unique|Exists>>
     */
    public function rules(): array
    {
        $translator = $this->route('translator');

        if (!$translator instanceof Translator) {
            abort(404);
        }

        /** @var string|null $connection Configured database connection name. */
        $connection = config('languages.db_connection');
        /** @var string $table Configured translator table name. */
        $table = config('languages.table_translators');
        return [
            'email' => ['required', 'email', Rule::unique($connection . '.' . $table, 'email')->ignore($translator)],
            'phone' => ['nullable', 'string', Rule::unique($connection . '.' . $table, 'phone')->ignore($translator)],
            'admin' => 'nullable|bool',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'languages' => $this->boolean('admin') ? 'nullable|array' : 'required|array|min:1',
            'languages.*' => ['integer', 'distinct', Rule::exists(Language::class, 'id')],
        ];
    }

    /**
     * Return profile attributes only, leaving the existing password untouched.
     *
     * @return array<string, mixed>
     */
    public function translatorAttributes(): array
    {
        /** @var array{email: string, phone?: string|null, first_name: string, last_name: string, admin?: bool|0|1|'0'|'1'|null} $attributes Validated profile fields. */
        $attributes = $this->safe()->only(['email', 'phone', 'first_name', 'last_name', 'admin']);
        $attributes['admin'] = $this->boolean('admin');
        return $attributes;
    }
}
