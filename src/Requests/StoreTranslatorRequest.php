<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
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
     * @return array<string, string|list<string|Unique>>
     */
    public function rules(): array
    {
        /** @var string|null $connection Configured database connection name. */
        $connection = config('languages.db_connection');
        /** @var string $table Configured translator table name. */
        $table = config('languages.table_translators');
        return [
            'email' => ['required', 'email', Rule::unique($connection . '.' . $table, 'email')],
            'phone' => ['nullable', 'string', Rule::unique($connection . '.' . $table, 'phone')],
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
        /** @var array{email: string, phone?: string|null, first_name: string, last_name: string, admin?: bool|0|1|'0'|'1'|null} $attributes Validated profile fields. */
        $attributes = $this->safe()->only(['email', 'phone', 'first_name', 'last_name', 'admin']);
        /** @var string $password Validated by required|string|min:8. */
        $password = $this->validated('password');
        $attributes['password'] = Hash::make($password);

        return $attributes;
    }
}
