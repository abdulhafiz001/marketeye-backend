<?php

namespace App\Support;

class AuthValidation
{
    /** @return list<string> */
    public static function emailRules(): array
    {
        $rule = app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';

        return ['required', $rule, 'max:255'];
    }

    /** @return list<string> */
    public static function phoneRules(): array
    {
        return ['nullable', 'string', 'regex:/^\d{10,15}$/'];
    }

    /** @return list<string> */
    public static function displayNameRules(): array
    {
        return ['required', 'string', 'min:2', 'max:80', 'regex:/^[\p{L}][\p{L}\s\'-]{0,78}[\p{L}]$/u'];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'email.email' => 'Enter a real email address on a valid domain.',
            'phone.regex' => 'Phone must be 10 to 15 digits.',
            'name.min' => 'Display name must be at least 2 characters.',
            'name.max' => 'Display name cannot be longer than 80 characters.',
            'name.regex' => 'Name can only include letters, spaces, hyphens, and apostrophes.',
        ];
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return $digits !== '' ? $digits : null;
    }
}
