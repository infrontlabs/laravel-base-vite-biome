<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id', 'category_id',
    'amount_cents', 'currency',
    'posted_date', 'authorized_date', 'pending_date',
    'description', 'merchant_name', 'raw_description',
    'source', 'status',
    'plaid_transaction_id', 'plaid_item_id', 'pending_plaid_transaction_id',
    'merged_into_id', 'merged_from_transaction_id',
    'excluded_from_budget', 'notes', 'tags',
])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'posted_date' => 'date',
            'authorized_date' => 'date',
            'pending_date' => 'date',
            'excluded_from_budget' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function mergedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_from_transaction_id');
    }
}
