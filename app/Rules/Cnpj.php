<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $value);

        if (strlen($cnpj) !== 14) {
            $fail(__('validation.cnpj'));

            return;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail(__('validation.cnpj'));

            return;
        }

        for ($t = 12; $t < 14; $t++) {
            $weights = $t === 12
                ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
                : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

            $sum = 0;

            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cnpj[$i] * $weights[$i];
            }

            $digit = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);

            if ((int) $cnpj[$t] !== $digit) {
                $fail(__('validation.cnpj'));

                return;
            }
        }
    }
}
