<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SaudiPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^(05|5|9665)\d{8}$/', $value)) {
            $fail('يجب أن يكون رقم الهاتف سعودياً صحيحاً ومكوناً من 9 أرقام تبدأ بـ 5، أو يبدأ بـ 05.');
        }
    }

    /**
     * Format the phone number to 9665XXXXXXXX for database storage.
     */
    public static function format($phone)
    {
        if (! $phone) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        if (str_starts_with($phone, '05')) {
            return '966'.substr($phone, 1);
        } elseif (str_starts_with($phone, '5')) {
            return '966'.$phone;
        }

        return $phone;
    }
}
