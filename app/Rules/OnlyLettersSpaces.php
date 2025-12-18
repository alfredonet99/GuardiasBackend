<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OnlyLettersSpaces implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Permite: letras (incluye acentos), espacios, guion y apóstrofe
        $pattern = "/^[\p{L}\s]+$/u";

        if (!is_string($value) || !preg_match($pattern, $value)) {
            $fail('El campo :attribute solo puede contener letras y espacios.');
        }
    }
}
