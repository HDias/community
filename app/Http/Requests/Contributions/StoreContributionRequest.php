<?php

namespace App\Http\Requests\Contributions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContributionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $communityId = $this->query('community');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('community_user', 'user_id')->where('community_id', $communityId),
            ],
            'reference_month' => ['required', 'date'],
        ];
    }
}
