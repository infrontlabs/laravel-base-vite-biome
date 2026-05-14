<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'account_id' => ['sometimes', 'exists:accounts,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'amount' => ['sometimes', 'numeric'],
            'date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:pending,posted,void'],
            'description' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'excluded_from_budget' => ['sometimes', 'boolean'],
        ];
    }
}
