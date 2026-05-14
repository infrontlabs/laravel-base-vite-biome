<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:pending,posted'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'excluded_from_budget' => ['boolean'],
        ];
    }
}
