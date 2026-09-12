<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Riomigal\Languages\Models\Translator;

class UpdateTranslatorPasswordRequest extends FormRequest
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
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|same:new_password',
        ];
    }

    /**
     * Return only the validated and hashed replacement password.
     *
     * @return array<string, string>
     */
    public function translatorAttributes(): array
    {
        return ['password' => Hash::make($this->validated('new_password'))];
    }
}
