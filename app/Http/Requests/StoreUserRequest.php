<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Deliberately excludes 'super-admin' — creating one requires
            // direct database/artisan access, never this endpoint.
            'role' => ['required', 'string', Rule::in(['admin', 'doctor'])],

            // branch_id is only actually used from this input when the
            // requester is super-admin — the Controller overrides it to
            // the requester's own branch otherwise (an admin can only
            // ever create users in their own branch). Still validated
            // here so a super-admin's explicit choice is checked.
            'branch_id' => ['required', 'integer', 'exists:branches,id'],

            'specialty_id' => ['required_if:role,doctor', 'integer', 'exists:specialties,id'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }
}
