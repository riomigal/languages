<?php

namespace Riomigal\Languages\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'string|email|required',
            'password' => 'required|string',
            'remember' => 'boolean',
        ];
    }
}
