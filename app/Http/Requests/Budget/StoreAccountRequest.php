<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:checking,savings,credit_card,mortgage,auto_loan,student_loan,cash,other_liability,other_asset'],
            'subkind' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'include_in_safe_to_spend' => ['boolean'],
            'include_in_net_worth' => ['boolean'],
        ];
    }
}
