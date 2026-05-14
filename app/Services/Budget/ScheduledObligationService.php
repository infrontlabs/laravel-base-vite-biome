<?php

namespace App\Services\Budget;

use App\Models\ObligationInstance;
use App\Models\ScheduledObligation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ScheduledObligationService
{
    public function __construct(private ObligationMaterializer $materializer) {}

    /**
     * Earliest upcoming paycheck across all active paycheck obligations.
     */
    public function nextPaycheckDate(?CarbonImmutable $from = null): ?CarbonImmutable
    {
        $from ??= CarbonImmutable::today();
        $this->materializer->run();

        $row = ObligationInstance::query()
            ->whereHas('obligation', fn (Builder $q) => $q
                ->where('kind', 'paycheck')
                ->where('is_active', true)
            )
            ->where('due_date', '>=', $from->toDateString())
            ->where('status', 'expected')
            ->orderBy('due_date')
            ->first();

        return $row ? CarbonImmutable::parse($row->due_date) : null;
    }

    /**
     * Upcoming obligation instances between today and the supplied end (inclusive),
     * eager-loaded with their obligation + account.
     *
     * @return Collection<int,ObligationInstance>
     */
    public function upcomingBetween(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $this->materializer->run();

        return ObligationInstance::query()
            ->with(['obligation.account', 'obligation.category'])
            ->where('status', 'expected')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * @return Collection<int,ScheduledObligation>
     */
    public function activePaychecks(): Collection
    {
        return ScheduledObligation::query()
            ->where('kind', 'paycheck')
            ->where('is_active', true)
            ->get();
    }
}
