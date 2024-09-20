<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserHandleRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-_\.]{1,28}[a-zA-Z0-9]$/', $value)) {
            $fail('The :attribute must be 3-30 characters long, start and end with a letter or number, and may only contain letters, numbers, dashes, underscores, and periods.');
            //remove period (optional)
        } elseif (preg_match('/^[0-9]+$/', $value)) {
            $fail('The :attribute must contain at least one letter.');
        }
    }
}
