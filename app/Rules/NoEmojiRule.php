<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoEmojiRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $emojiPattern = '/[\x{1F600}-\x{1F64F}]|'.    // Emoticons
            '[\x{1F300}-\x{1F5FF}]|'.    // Misc Symbols and Pictographs
            '[\x{1F680}-\x{1F6FF}]|'.    // Transport and Map symbols
            '[\x{1F700}-\x{1F77F}]|'.    // Alchemical symbols
            '[\x{1F780}-\x{1F7FF}]|'.    // Geometric shapes extended
            '[\x{1F800}-\x{1F8FF}]|'.    // Supplemental Arrows-C
            '[\x{2600}-\x{26FF}]|'.      // Misc symbols
            '[\x{2700}-\x{27BF}]|'.      // Dingbats
            '[\x{FE00}-\x{FE0F}]|'.      // Variation Selectors
            '[\x{1F900}-\x{1F9FF}]|'.    // Supplemental Symbols and Pictographs
            '[\x{1FA70}-\x{1FAFF}]|'.    // Symbols and Pictographs Extended-A
            '[\x{200D}\x{20E3}]|'.       // Zero-width Joiners and Combining Enclosing Keycap
            '[\x{1F1E0}-\x{1F1FF}]|'.    // Flags
            '[\x{E0020}-\x{E007F}]|'.    // Tags
            '[\x{1F004}\x{1F0CF}]|'.     // Mahjong and cards
            '[\x{1F3FB}-\x{1F3FF}]/u';    // Skin tone modifiers

        if (preg_match($emojiPattern, $value)) {
            $fail('The :attribute field cannot contain emojis.');
        }
    }
}
