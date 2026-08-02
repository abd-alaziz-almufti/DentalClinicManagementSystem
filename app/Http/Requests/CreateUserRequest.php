<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8'],
            'role'           => ['required', 'string', 'in:super-admin,admin,doctor'],
            'branch_id'      => ['required', 'exists:branches,id'],
            'license_number' => ['required_if:role,doctor', 'nullable', 'string', 'max:100'],
            'specialty_id'   => ['nullable', 'exists:specialties,id'],
            'color'          => ['nullable', 'string', 'max:50'],
        ];
    }
}
