<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Riomigal\Languages\Models\Translator;

class StoreTranslatorRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique(config('languages.db_connection') . '.' . config('languages.table_translators'), 'email')],
            'phone' => ['nullable', 'string', Rule::unique(config('languages.db_connection') . '.' . config('languages.table_translators'), 'phone')],
            'admin' => 'nullable|bool',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
            'languages' => $this->boolean('admin') ? 'nullable|array' : 'required|array',
        ];
    }

    /**
     * Return model attributes with a hashed password; sync languages separately.
     *
     * @return array<string, mixed>
     */
    public function translatorAttributes(): array
    {
        $attributes = $this->safe()->only(['email', 'phone', 'first_name', 'last_name', 'admin']);
        $attributes['password'] = Hash::make($this->validated('password'));

        return $attributes;
    }
}
