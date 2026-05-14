<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\StoreObligationRequest;
use App\Http\Requests\Budget\UpdateObligationRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\ObligationInstance;
use App\Models\ScheduledObligation;
use App\Services\Budget\ObligationMaterializer;
use App\Services\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ObligationsController extends Controller
{
    /**
     * Multipliers to normalize a single occurrence amount into a monthly equivalent.
     * weekly: 52/12, biweekly: 26/12, semimonthly: 2, monthly: 1, quarterly: 1/3, annual: 1/12, custom: 1.
     */
    private const MONTHLY_MULTIPLIERS = [
        'weekly' => 52 / 12,
        'biweekly' => 26 / 12,
        'semimonthly' => 2.0,
        'monthly' => 1.0,
        'quarterly' => 1 / 3,
        'annual' => 1 / 12,
        'custom' => 1.0,
    ];

    public function index(ObligationMaterializer $materializer): Response
    {
        $materializer->run();

        $obligations = ScheduledObligation::query()
            ->with(['account', 'category'])
            ->orderBy('is_active', 'desc')
            ->orderBy('kind')
            ->orderBy('name')
            ->get()
            ->map(fn (ScheduledObligation $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'kind' => $o->kind,
                'direction' => $o->direction,
                'amount_cents' => $o->amount_cents,
                'frequency' => $o->frequency,
                'interval' => $o->interval,
                'anchor_date' => $o->anchor_date->toDateString(),
                'is_active' => $o->is_active,
                'autopay' => $o->autopay,
                'cancel_url' => $o->cancel_url,
                'last_reviewed_at' => $o->last_reviewed_at?->toDateString(),
                'account_name' => $o->account?->name,
                'category_name' => $o->category?->name,
            ]);

        $today = CarbonImmutable::today();
        $end = $today->addDays(ObligationMaterializer::HORIZON_DAYS);

        $upcoming = ObligationInstance::query()
            ->with(['obligation.account', 'obligation.category'])
            ->where('status', 'expected')
            ->whereBetween('due_date', [$today->toDateString(), $end->toDateString()])
            ->orderBy('due_date')
            ->get()
            ->map(fn (ObligationInstance $i) => [
                'id' => $i->id,
                'name' => $i->obligation->name,
                'kind' => $i->obligation->kind,
                'direction' => $i->obligation->direction,
                'due_date' => $i->due_date->toDateString(),
                'amount_cents' => $i->expected_amount_cents,
                'status' => $i->status,
                'account_name' => $i->obligation->account?->name,
            ]);

        return Inertia::render('obligations/index', [
            'obligations' => $obligations,
            'upcoming' => $upcoming,
            'subscriptionRollup' => $this->subscriptionRollup(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('obligations/create', [
            'accounts' => $this->accountOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreObligationRequest $request, ObligationMaterializer $materializer): RedirectResponse
    {
        $data = $request->validated();
        $cents = Money::fromDollars($data['amount'])->cents;

        ScheduledObligation::create([
            'name' => $data['name'],
            'kind' => $data['kind'],
            'direction' => $data['direction'],
            'account_id' => $data['account_id'],
            'category_id' => $data['category_id'] ?? null,
            'amount_cents' => $cents,
            'currency' => 'USD',
            'frequency' => $data['frequency'],
            'interval' => $data['interval'] ?? 1,
            'anchor_date' => $data['anchor_date'],
            'day_of_month' => $data['day_of_month'] ?? null,
            'secondary_day_of_month' => $data['secondary_day_of_month'] ?? null,
            'day_of_week' => $data['day_of_week'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'autopay' => $data['autopay'] ?? false,
            'cancel_url' => $data['cancel_url'] ?? null,
            'last_reviewed_at' => $data['last_reviewed_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        $materializer->run();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recurring item added.']);

        return to_route('obligations.index');
    }

    public function edit(ScheduledObligation $obligation): Response
    {
        return Inertia::render('obligations/edit', [
            'obligation' => [
                'id' => $obligation->id,
                'name' => $obligation->name,
                'kind' => $obligation->kind,
                'direction' => $obligation->direction,
                'account_id' => $obligation->account_id,
                'category_id' => $obligation->category_id,
                'amount_cents' => $obligation->amount_cents,
                'frequency' => $obligation->frequency,
                'interval' => $obligation->interval,
                'anchor_date' => $obligation->anchor_date->toDateString(),
                'day_of_month' => $obligation->day_of_month,
                'secondary_day_of_month' => $obligation->secondary_day_of_month,
                'day_of_week' => $obligation->day_of_week,
                'end_date' => $obligation->end_date?->toDateString(),
                'autopay' => $obligation->autopay,
                'cancel_url' => $obligation->cancel_url,
                'last_reviewed_at' => $obligation->last_reviewed_at?->toDateString(),
                'is_active' => $obligation->is_active,
                'notes' => $obligation->notes,
            ],
            'accounts' => $this->accountOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(
        UpdateObligationRequest $request,
        ScheduledObligation $obligation,
        ObligationMaterializer $materializer,
    ): RedirectResponse {
        $data = $request->validated();
        $payload = collect($data)->except('amount')->all();
        if (array_key_exists('amount', $data)) {
            $payload['amount_cents'] = Money::fromDollars($data['amount'])->cents;
        }

        $obligation->fill($payload)->save();
        $materializer->run();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recurring item updated.']);

        return to_route('obligations.index');
    }

    public function destroy(ScheduledObligation $obligation): RedirectResponse
    {
        $obligation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recurring item deleted.']);

        return to_route('obligations.index');
    }

    /**
     * @return array{monthly_cents:int, annual_cents:int, count:int}
     */
    private function subscriptionRollup(): array
    {
        $subscriptions = ScheduledObligation::query()
            ->where('kind', 'subscription')
            ->where('is_active', true)
            ->where('direction', 'outflow')
            ->get(['amount_cents', 'frequency', 'interval']);

        $monthlyCents = 0.0;
        foreach ($subscriptions as $sub) {
            $multiplier = self::MONTHLY_MULTIPLIERS[$sub->frequency] ?? 1.0;
            $interval = max(1, (int) $sub->interval);
            $monthlyCents += ($sub->amount_cents * $multiplier) / $interval;
        }

        $monthlyCents = (int) round($monthlyCents);

        return [
            'monthly_cents' => $monthlyCents,
            'annual_cents' => $monthlyCents * 12,
            'count' => $subscriptions->count(),
        ];
    }

    private function accountOptions(): array
    {
        return Account::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'kind'])
            ->toArray();
    }

    private function categoryOptions(): array
    {
        return Category::query()
            ->where('is_archived', false)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'group', 'icon'])
            ->toArray();
    }
}
