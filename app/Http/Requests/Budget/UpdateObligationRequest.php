<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObligationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'kind' => ['sometimes', 'in:bill,subscription,paycheck,savings_transfer,debt_payment,other'],
            'direction' => ['sometimes', 'in:inflow,outflow'],
            'account_id' => ['sometimes', 'exists:accounts,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'frequency' => ['sometimes', 'in:weekly,biweekly,semimonthly,monthly,quarterly,annual,custom'],
            'interval' => ['sometimes', 'integer', 'min:1', 'max:52'],
            'anchor_date' => ['sometimes', 'date'],
            'day_of_month' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'secondary_day_of_month' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'day_of_week' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:6'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'autopay' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
