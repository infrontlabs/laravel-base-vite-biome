<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\StoreAccountRequest;
use App\Http\Requests\Budget\UpdateAccountRequest;
use App\Models\Account;
use App\Services\Money\Money;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountsController extends Controller
{
    private const LIABILITY_KINDS = ['credit_card', 'mortgage', 'auto_loan', 'student_loan', 'other_liability'];

    private const NON_SPEND_KINDS = ['savings', 'mortgage', 'auto_loan', 'student_loan', 'other_liability', 'other_asset'];

    public function index(): Response
    {
        $accounts = Account::query()
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (Account $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'kind' => $a->kind,
                'subkind' => $a->subkind,
                'current_balance_cents' => $a->current_balance_cents,
                'available_balance_cents' => $a->available_balance_cents,
                'is_liability' => $a->is_liability,
                'is_active' => $a->is_active,
                'manual_only' => $a->manual_only,
                'include_in_safe_to_spend' => $a->include_in_safe_to_spend,
                'include_in_net_worth' => $a->include_in_net_worth,
                'mask' => $a->mask,
            ]);

        return Inertia::render('accounts/index', [
            'accounts' => $accounts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('accounts/create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $opening = isset($data['opening_balance'])
            ? Money::fromDollars($data['opening_balance'])->cents
            : 0;

        Account::create([
            'name' => $data['name'],
            'kind' => $data['kind'],
            'subkind' => $data['subkind'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'current_balance_cents' => $opening,
            'is_liability' => in_array($data['kind'], self::LIABILITY_KINDS, true),
            'manual_only' => true,
            'include_in_safe_to_spend' => $data['include_in_safe_to_spend']
                ?? ! in_array($data['kind'], self::NON_SPEND_KINDS, true),
            'include_in_net_worth' => $data['include_in_net_worth'] ?? true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account created.']);

        return to_route('accounts.index');
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $data = $request->validated();
        $payload = collect($data)
            ->except('current_balance')
            ->all();

        if (array_key_exists('current_balance', $data)) {
            $payload['current_balance_cents'] = Money::fromDollars($data['current_balance'])->cents;
            $payload['as_of'] = now();
        }

        $account->fill($payload)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account updated.']);

        return to_route('accounts.index');
    }
}
