<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'scheduled_obligation_id', 'due_date',
    'expected_amount_cents', 'transaction_id',
    'status', 'matched_at',
])]
class ObligationInstance extends Model
{
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'expected_amount_cents' => 'integer',
            'matched_at' => 'datetime',
        ];
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(ScheduledObligation::class, 'scheduled_obligation_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
