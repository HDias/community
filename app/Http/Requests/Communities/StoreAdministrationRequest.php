<?php

namespace App\Http\Requests\Communities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after:started_at'],
        ];
    }
}
