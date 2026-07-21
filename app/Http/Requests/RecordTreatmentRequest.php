<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id'      => ['required', 'exists:services,id'],
            'tooth_number'    => ['nullable', 'integer'],
            'quantity'        => ['sometimes', 'integer', 'min:1'],
            'unit_price'      => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string'],
        ];
    }
}
