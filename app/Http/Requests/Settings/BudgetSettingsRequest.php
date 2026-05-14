<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class BudgetSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'buffer_threshold' => ['required', 'numeric', 'min:0'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_account_id' => ['nullable', 'exists:accounts,id'],
        ];
    }
}
