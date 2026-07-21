<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DentalChartEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tooth_id'           => ['required', 'exists:teeth,id'],
            'tooth_condition_id' => ['required', 'exists:tooth_conditions,id'],
            'tooth_surface_id'   => ['nullable', 'exists:tooth_surfaces,id'],
            'visit_service_id'   => ['nullable', 'exists:visit_services,id'],
            'entry_type'         => ['required', 'string', 'in:diagnosis,treatment'],
            'notes'              => ['nullable', 'string'],
        ];
    }
}
