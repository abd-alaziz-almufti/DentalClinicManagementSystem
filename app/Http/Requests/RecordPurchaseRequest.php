<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user && !$user->hasRole('super-admin')) {
            $this->merge(['branch_id' => $user->branch_id]);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id'              => ['required', 'exists:branches,id'],
            'supplier_id'            => ['nullable', 'exists:suppliers,id'],
            'notes'                  => ['nullable', 'string'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost'      => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
