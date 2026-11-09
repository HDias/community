<?php

namespace App\Http\Requests\Contributions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContributionReversalRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reversal_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
