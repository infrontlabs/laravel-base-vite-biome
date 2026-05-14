<?php

namespace App\Services\Budget;

use Carbon\CarbonImmutable;

final readonly class SafeToSpendResult
{
    public function __construct(
        public int $safeToSpendCents,
        public CarbonImmutable $horizonEnd,
        public int $liquidCents,
        public int $pendingManualOutflowsCents,
        public int $upcomingObligationsCents,
        public int $upcomingInflowsCents,
        public int $bufferCents,
    ) {}

    /**
     * @return array{label:string, amount_cents:int}[]
     */
    public function breakdown(): array
    {
        return [
            ['label' => 'Liquid balance', 'amount_cents' => $this->liquidCents],
            ['label' => 'Pending manual outflows', 'amount_cents' => $this->pendingManualOutflowsCents],
            ['label' => 'Upcoming obligations', 'amount_cents' => -$this->upcomingObligationsCents],
            ['label' => 'Upcoming inflows', 'amount_cents' => $this->upcomingInflowsCents],
            ['label' => 'Buffer threshold', 'amount_cents' => -$this->bufferCents],
        ];
    }

    public function toArray(): array
    {
        return [
            'safe_to_spend_cents' => $this->safeToSpendCents,
            'horizon_end' => $this->horizonEnd->toDateString(),
            'liquid_cents' => $this->liquidCents,
            'pending_manual_outflows_cents' => $this->pendingManualOutflowsCents,
            'upcoming_obligations_cents' => $this->upcomingObligationsCents,
            'upcoming_inflows_cents' => $this->upcomingInflowsCents,
            'buffer_cents' => $this->bufferCents,
            'breakdown' => $this->breakdown(),
        ];
    }
}
