<?php

namespace App\Http\Requests\Api\V1;

use App\Support\AuthValidation;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => AuthValidation::normalizeEmail($this->input('email')),
            'phone' => AuthValidation::normalizePhone($this->input('phone')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => AuthValidation::displayNameRules(),
            'email' => AuthValidation::emailRules(),
            'password' => ['required', 'string', 'min:8'],
            'phone' => AuthValidation::phoneRules(),
        ];
    }

    public function messages(): array
    {
        return AuthValidation::messages();
    }
}
