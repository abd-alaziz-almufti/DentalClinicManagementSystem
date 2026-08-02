<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('branch')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],

            // FR-004: free text, no format validation, never touches
            // financial calculations.
            'tax_number' => ['sometimes', 'nullable', 'string', 'max:50'],

            // FR-005: cosmetic string, no real currency validation.
            'currency_code' => ['sometimes', 'string', 'max:10'],
        ];
    }
}
