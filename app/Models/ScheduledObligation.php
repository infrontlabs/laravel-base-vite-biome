<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'kind', 'direction',
    'account_id', 'category_id',
    'amount_cents', 'currency',
    'frequency', 'interval', 'anchor_date',
    'day_of_month', 'secondary_day_of_month', 'day_of_week',
    'end_date', 'autopay', 'is_active',
    'last_materialized_through', 'notes',
])]
class ScheduledObligation extends Model
{
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'interval' => 'integer',
            'anchor_date' => 'date',
            'day_of_month' => 'integer',
            'secondary_day_of_month' => 'integer',
            'day_of_week' => 'integer',
            'end_date' => 'date',
            'autopay' => 'boolean',
            'is_active' => 'boolean',
            'last_materialized_through' => 'date',
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

    public function instances(): HasMany
    {
        return $this->hasMany(ObligationInstance::class);
    }

    /**
     * Return every occurrence date within [$start, $end] (inclusive).
     */
    public function occurrencesBetween(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $occurrences = new Collection;

        if ($end->lt($start)) {
            return $occurrences;
        }

        $hardEnd = $this->end_date ? CarbonImmutable::parse($this->end_date) : null;
        if ($hardEnd && $hardEnd->lt($start)) {
            return $occurrences;
        }

        $cap = $hardEnd && $hardEnd->lt($end) ? $hardEnd : $end;

        $cursor = $this->firstOccurrenceOnOrAfter($start);

        $safety = 0;
        while ($cursor !== null && $cursor->lte($cap) && $safety++ < 5000) {
            $occurrences->push($cursor);
            $cursor = $this->advance($cursor);
        }

        return $occurrences;
    }

    /**
     * Next occurrence strictly after the given date.
     */
    public function nextOccurrenceAfter(CarbonImmutable $after): ?CarbonImmutable
    {
        $candidate = $this->firstOccurrenceOnOrAfter($after->addDay());
        $hardEnd = $this->end_date ? CarbonImmutable::parse($this->end_date) : null;
        if ($candidate === null || ($hardEnd && $candidate->gt($hardEnd))) {
            return null;
        }

        return $candidate;
    }

    private function firstOccurrenceOnOrAfter(CarbonImmutable $target): ?CarbonImmutable
    {
        $anchor = CarbonImmutable::parse($this->anchor_date);

        if ($anchor->gte($target)) {
            return $anchor;
        }

        return $this->advanceUntil($anchor, $target);
    }

    private function advanceUntil(CarbonImmutable $from, CarbonImmutable $target): ?CarbonImmutable
    {
        $cursor = $from;
        $safety = 0;
        while ($cursor->lt($target) && $safety++ < 5000) {
            $next = $this->advance($cursor);
            if ($next === null || $next->lte($cursor)) {
                return null;
            }
            $cursor = $next;
        }

        return $cursor;
    }

    private function advance(CarbonImmutable $from): ?CarbonImmutable
    {
        $interval = max(1, (int) $this->interval);

        return match ($this->frequency) {
            'weekly' => $from->addDays(7 * $interval),
            'biweekly' => $from->addDays(14 * $interval),
            'semimonthly' => $this->advanceSemimonthly($from),
            'monthly' => $this->advanceMonthly($from, $interval),
            'quarterly' => $this->advanceMonthly($from, 3 * $interval),
            'annual' => $this->advanceMonthly($from, 12 * $interval),
            'custom' => null,
            default => null,
        };
    }

    private function advanceMonthly(CarbonImmutable $from, int $months): CarbonImmutable
    {
        $day = $this->day_of_month ?: $from->day;
        $next = $from->addMonthsNoOverflow($months);

        return $next->setDay(min($day, $next->daysInMonth));
    }

    private function advanceSemimonthly(CarbonImmutable $from): CarbonImmutable
    {
        $first = $this->day_of_month ?: 1;
        $second = $this->secondary_day_of_month ?: 15;
        [$first, $second] = $first <= $second ? [$first, $second] : [$second, $first];

        if ($from->day < $first) {
            return $from->setDay(min($first, $from->daysInMonth));
        }
        if ($from->day < $second) {
            return $from->setDay(min($second, $from->daysInMonth));
        }

        $next = $from->addMonthNoOverflow();

        return $next->setDay(min($first, $next->daysInMonth));
    }
}
