<?php

namespace App\Http\Requests\Contributions;

use App\Enums\DonorType;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $communityId = $this->query('community');

        return [
            'donor_type' => ['required', Rule::enum(DonorType::class)],
            'user_id' => [
                'nullable',
                'integer',
                'required_if:donor_type,'.DonorType::Member->value,
                Rule::exists('community_user', 'user_id')->where('community_id', $communityId),
            ],
            'donor_name' => [
                'nullable',
                'string',
                'max:255',
                'required_if:donor_type,'.DonorType::Individual->value.','.DonorType::Organization->value,
            ],
            'donor_document' => [
                'nullable',
                'string',
                'required_if:donor_type,'.DonorType::Individual->value.','.DonorType::Organization->value,
                Rule::when(
                    $this->input('donor_type') === DonorType::Individual->value,
                    [new Cpf],
                ),
                Rule::when(
                    $this->input('donor_type') === DonorType::Organization->value,
                    [new Cnpj],
                ),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('donor_document')) {
            $this->merge([
                'donor_document' => preg_replace('/\D/', '', (string) $this->input('donor_document')),
            ]);
        }
    }
}
