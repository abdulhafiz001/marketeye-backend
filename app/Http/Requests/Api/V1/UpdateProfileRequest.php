<?php

namespace App\Http\Requests\Api\V1;

use App\Support\AuthValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'name' => AuthValidation::displayNameRules(),
            'email' => [...AuthValidation::emailRules(), Rule::unique('users', 'email')->ignore($userId)],
            'phone' => AuthValidation::phoneRules(),
        ];
    }

    public function messages(): array
    {
        return AuthValidation::messages();
    }
}
