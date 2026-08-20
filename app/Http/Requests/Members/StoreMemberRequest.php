<?php

namespace App\Http\Requests\Members;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge(['cpf' => preg_replace('/\D/', '', $this->input('cpf'))]);
        }

        if ($this->filled('phone')) {
            $this->merge(['phone' => preg_replace('/\D/', '', $this->input('phone'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'social_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'cpf' => ['required', 'string', new Cpf, 'unique:profiles,cpf'],
            'birth_date' => ['required', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:20'],
            'profession' => ['nullable', 'string', 'max:255'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_neighborhood' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'max:2'],
            'address_zip' => ['nullable', 'string', 'max:9'],
        ];
    }
}
