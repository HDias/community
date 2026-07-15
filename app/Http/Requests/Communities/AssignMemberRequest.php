<?php

namespace App\Http\Requests\Communities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMemberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $community = $this->route('administration')->community;

        return [
            'user_id' => ['required', Rule::exists('community_user', 'user_id')->where('community_id', $community->id)],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('community_id', $community->id)],
        ];
    }
}
