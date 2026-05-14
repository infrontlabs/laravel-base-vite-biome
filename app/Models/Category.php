<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'parent_id', 'group',
    'plaid_primary', 'plaid_detailed',
    'monthly_target_cents',
    'color', 'icon', 'is_archived', 'position',
])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'monthly_target_cents' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
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
