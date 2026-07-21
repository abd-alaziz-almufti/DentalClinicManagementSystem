<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user && !$user->hasRole('super-admin')) {
            $this->merge([
                'branch_id' => $user->branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'doctor_profile_id' => ['required', 'exists:doctor_profiles,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i:s,H:i'],
            'end_time' => ['required', 'date_format:H:i:s,H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'branch_id' => ['required', 'exists:branches,id'],
        ];
    }
}
