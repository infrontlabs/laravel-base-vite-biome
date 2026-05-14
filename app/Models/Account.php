<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'kind', 'subkind', 'currency',
    'current_balance_cents', 'available_balance_cents', 'as_of',
    'is_liability', 'is_active', 'manual_only',
    'plaid_item_id', 'plaid_account_id', 'mask',
    'include_in_safe_to_spend', 'include_in_net_worth', 'position',
])]
class Account extends Model
{
    protected function casts(): array
    {
        return [
            'as_of' => 'datetime',
            'is_liability' => 'boolean',
            'is_active' => 'boolean',
            'manual_only' => 'boolean',
            'include_in_safe_to_spend' => 'boolean',
            'include_in_net_worth' => 'boolean',
            'current_balance_cents' => 'integer',
            'available_balance_cents' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scheduledObligations(): HasMany
    {
        return $this->hasMany(ScheduledObligation::class);
    }
}
