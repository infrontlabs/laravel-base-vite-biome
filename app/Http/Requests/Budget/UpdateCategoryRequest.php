<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'parent_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'group' => ['sometimes', 'in:fixed,flexible,income,transfer,savings,debt_payment'],
            'monthly_target' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_archived' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer'],
        ];
    }
}
