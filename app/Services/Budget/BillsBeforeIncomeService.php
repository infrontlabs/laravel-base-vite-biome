<?php

namespace App\Services\Budget;

use App\Models\Account;
use App\Models\ObligationInstance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class BillsBeforeIncomeService
{
    public const LOOKAHEAD_DAYS = 30;

    public function __construct(private ObligationMaterializer $materializer) {}

    public function compute(?CarbonImmutable $today = null): BillsBeforeIncomeResult
    {
        $today ??= CarbonImmutable::today();
        $this->materializer->run($today);

        $end = $today->addDays(self::LOOKAHEAD_DAYS);

        $rows = ObligationInstance::query()
            ->with(['obligation.account'])
            ->where('status', 'expected')
            ->whereBetween('due_date', [$today->toDateString(), $end->toDateString()])
            ->whereHas('obligation', fn (Builder $q) => $q->where('is_active', true))
            ->orderBy('due_date')
            ->get();

        $billsBefore = [];
        $sumOutflowCents = 0;
        foreach ($rows as $row) {
            if ($row->obligation->direction === 'inflow') {
                break;
            }
            if ($row->obligation->account?->include_in_safe_to_spend) {
                $billsBefore[] = $row;
                $sumOutflowCents += (int) $row->expected_amount_cents;
            }
        }

        $liquidCents = (int) Account::query()
            ->where('include_in_safe_to_spend', true)
            ->where('is_active', true)
            ->get()
            ->sum(fn (Account $a) => $a->available_balance_cents ?? $a->current_balance_cents);

        $coverageGapCents = $sumOutflowCents - $liquidCents;

        return new BillsBeforeIncomeResult(
            bills: $billsBefore,
            outflowCents: $sumOutflowCents,
            liquidCents: $liquidCents,
            coverageGapCents: $coverageGapCents,
        );
    }
}
