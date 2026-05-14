<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\Budget\BillsBeforeIncomeService;
use App\Services\Budget\SafeToSpendCalculator;
use App\Services\Budget\ScheduledObligationService;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(
        SafeToSpendCalculator $safeToSpend,
        ScheduledObligationService $obligations,
        BillsBeforeIncomeService $billsBeforeIncome,
    ): Response {
        $today = CarbonImmutable::today();
        $result = $safeToSpend->compute($today);

        $upcoming = $obligations->upcomingBetween($today, $today->addDays(30))
            ->take(5)
            ->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->obligation->name,
                'kind' => $i->obligation->kind,
                'direction' => $i->obligation->direction,
                'due_date' => $i->due_date->toDateString(),
                'amount_cents' => $i->expected_amount_cents,
                'account_name' => $i->obligation->account?->name,
            ])
            ->values();

        $pending = Transaction::query()
            ->with(['account', 'category'])
            ->where('status', 'pending')
            ->orderByDesc('pending_date')
            ->take(10)
            ->get()
            ->map(fn ($t) => $this->summarize($t));

        $recent = Transaction::query()
            ->with(['account', 'category'])
            ->where('status', 'posted')
            ->orderByDesc('posted_date')
            ->take(10)
            ->get()
            ->map(fn ($t) => $this->summarize($t));

        $netWorthCents = (int) Account::query()
            ->where('is_active', true)
            ->where('include_in_net_worth', true)
            ->get()
            ->sum(fn (Account $a) => $a->is_liability
                ? -$a->current_balance_cents
                : $a->current_balance_cents);

        $bills = $billsBeforeIncome->compute($today);

        return Inertia::render('dashboard', [
            'safeToSpend' => $result->toArray(),
            'bufferZone' => $this->bufferZone($result->safeToSpendCents, $result->bufferCents),
            'upcomingObligations' => $upcoming,
            'pendingTransactions' => $pending,
            'recentTransactions' => $recent,
            'netWorthCents' => $netWorthCents,
            'billsBeforeIncome' => [
                'has_shortfall' => $bills->hasShortfall(),
                'count' => $bills->count(),
                'outflow_cents' => $bills->outflowCents,
                'liquid_cents' => $bills->liquidCents,
                'coverage_gap_cents' => $bills->coverageGapCents,
            ],
        ]);
    }

    private function summarize(Transaction $t): array
    {
        return [
            'id' => $t->id,
            'description' => $t->description,
            'amount_cents' => $t->amount_cents,
            'date' => ($t->posted_date ?? $t->pending_date)?->toDateString(),
            'status' => $t->status,
            'source' => $t->source,
            'account_name' => $t->account?->name,
            'category_name' => $t->category?->name,
        ];
    }

    private function bufferZone(int $safe, int $buffer): string
    {
        if ($safe < $buffer) {
            return 'red';
        }
        if ($safe < $buffer * 2) {
            return 'amber';
        }

        return 'green';
    }
}
