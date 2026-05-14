<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'subkind' => ['nullable', 'string', 'max:255'],
            'current_balance' => ['sometimes', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
            'include_in_safe_to_spend' => ['sometimes', 'boolean'],
            'include_in_net_worth' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer'],
        ];
    }
}
