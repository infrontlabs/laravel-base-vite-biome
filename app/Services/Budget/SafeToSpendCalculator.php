<?php

namespace App\Services\Budget;

use App\Models\Account;
use App\Models\ObligationInstance;
use App\Models\Transaction;
use App\Models\UserPreference;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SafeToSpendCalculator
{
    public const DEFAULT_BUFFER_CENTS = 50000;

    public const DEFAULT_HORIZON_DAYS = 14;

    public function __construct(private ScheduledObligationService $obligations) {}

    public function compute(?CarbonImmutable $today = null): SafeToSpendResult
    {
        $today ??= CarbonImmutable::today();
        $horizonEnd = $this->obligations->nextPaycheckDate($today)
            ?? $today->addDays(self::DEFAULT_HORIZON_DAYS);

        $liquid = $this->liquidBalanceCents();
        $pendingManual = $this->pendingManualOutflowCents();
        $upcomingOut = $this->upcomingObligationsCents($today, $horizonEnd);
        $upcomingIn = $this->upcomingInflowsCents($today, $horizonEnd);
        $buffer = (int) UserPreference::get('buffer_threshold_cents', self::DEFAULT_BUFFER_CENTS);

        $safeCents = $liquid + $pendingManual - $upcomingOut + $upcomingIn - $buffer;

        return new SafeToSpendResult(
            safeToSpendCents: $safeCents,
            horizonEnd: $horizonEnd,
            liquidCents: $liquid,
            pendingManualOutflowsCents: $pendingManual,
            upcomingObligationsCents: $upcomingOut,
            upcomingInflowsCents: $upcomingIn,
            bufferCents: $buffer,
        );
    }

    private function liquidBalanceCents(): int
    {
        return (int) Account::query()
            ->where('include_in_safe_to_spend', true)
            ->where('is_active', true)
            ->get()
            ->sum(fn (Account $a) => $a->available_balance_cents ?? $a->current_balance_cents);
    }

    private function pendingManualOutflowCents(): int
    {
        // amount_cents is already signed (outflow = negative)
        return (int) Transaction::query()
            ->where('status', 'pending')
            ->where('source', 'manual')
            ->whereHas('account', fn (Builder $q) => $q->where('include_in_safe_to_spend', true))
            ->sum('amount_cents');
    }

    private function upcomingObligationsCents(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return (int) ObligationInstance::query()
            ->where('status', 'expected')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('obligation', fn (Builder $q) => $q
                ->where('direction', 'outflow')
                ->whereHas('account', fn (Builder $a) => $a->where('include_in_safe_to_spend', true))
            )
            ->sum('expected_amount_cents');
    }

    private function upcomingInflowsCents(CarbonImmutable $start, CarbonImmutable $end): int
    {
        // Exclude the inflow that IS the horizon — that paycheck arrives on the boundary.
        $endExclusive = $end->subDay();
        if ($endExclusive->lt($start)) {
            return 0;
        }

        return (int) ObligationInstance::query()
            ->where('status', 'expected')
            ->whereBetween('due_date', [$start->toDateString(), $endExclusive->toDateString()])
            ->whereHas('obligation', fn (Builder $q) => $q->where('direction', 'inflow'))
            ->sum('expected_amount_cents');
    }
}
