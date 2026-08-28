<?php

namespace App\Http\Requests\Contributions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContributionSettingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'monthly_amount' => ['required', 'numeric', 'min:0.01'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
