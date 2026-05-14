<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BudgetSettingsRequest;
use App\Models\Account;
use App\Models\UserPreference;
use App\Services\Budget\SafeToSpendCalculator;
use App\Services\Money\Money;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BudgetSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/budget', [
            'preferences' => [
                'buffer_threshold_cents' => (int) UserPreference::get(
                    'buffer_threshold_cents',
                    SafeToSpendCalculator::DEFAULT_BUFFER_CENTS,
                ),
                'default_currency' => (string) UserPreference::get('default_currency', 'USD'),
                'default_account_id' => UserPreference::get('default_account_id'),
            ],
            'accounts' => Account::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray(),
        ]);
    }

    public function update(BudgetSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        UserPreference::set('buffer_threshold_cents', Money::fromDollars($data['buffer_threshold'])->cents);
        UserPreference::set('default_currency', strtoupper($data['default_currency']));
        UserPreference::set('default_account_id', $data['default_account_id'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Budget settings saved.']);

        return to_route('budget-settings.edit');
    }
}
