<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class StoreObligationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:bill,subscription,paycheck,savings_transfer,debt_payment,other'],
            'direction' => ['required', 'in:inflow,outflow'],
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:weekly,biweekly,semimonthly,monthly,quarterly,annual,custom'],
            'interval' => ['nullable', 'integer', 'min:1', 'max:52'],
            'anchor_date' => ['required', 'date'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'secondary_day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'end_date' => ['nullable', 'date', 'after_or_equal:anchor_date'],
            'autopay' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
