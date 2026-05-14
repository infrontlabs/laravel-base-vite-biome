<?php

namespace App\Services\Budget;

use Illuminate\Support\Collection;

final readonly class BillsBeforeIncomeResult
{
    public function __construct(
        public iterable $bills,
        public int $outflowCents,
        public int $liquidCents,
        public int $coverageGapCents,
    ) {}

    public function hasShortfall(): bool
    {
        return $this->coverageGapCents > 0;
    }

    public function count(): int
    {
        return $this->bills instanceof Collection
            ? $this->bills->count()
            : count($this->bills);
    }
}
