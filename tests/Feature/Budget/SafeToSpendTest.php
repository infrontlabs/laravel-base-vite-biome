<?php

use App\Models\Account;
use App\Models\ScheduledObligation;
use App\Models\Transaction;
use App\Models\UserPreference;
use App\Services\Budget\SafeToSpendCalculator;
use Carbon\CarbonImmutable;

function checking(int $cents): Account
{
    return Account::create([
        'name' => 'Checking',
        'kind' => 'checking',
        'currency' => 'USD',
        'current_balance_cents' => $cents,
        'include_in_safe_to_spend' => true,
    ]);
}

test('safe to spend with no obligations is liquid minus buffer', function () {
    checking(200000);
    UserPreference::set('buffer_threshold_cents', 50000);

    $result = app(SafeToSpendCalculator::class)->compute(
        CarbonImmutable::parse('2026-05-10'),
    );

    expect($result->safeToSpendCents)->toBe(150000);
});

test('pending manual outflows reduce safe to spend', function () {
    $account = checking(200000);
    UserPreference::set('buffer_threshold_cents', 0);

    Transaction::create([
        'account_id' => $account->id,
        'amount_cents' => -7500,
        'currency' => 'USD',
        'pending_date' => '2026-05-09',
        'description' => 'Pending coffee shop run',
        'source' => 'manual',
        'status' => 'pending',
    ]);

    $result = app(SafeToSpendCalculator::class)->compute(
        CarbonImmutable::parse('2026-05-10'),
    );

    expect($result->safeToSpendCents)->toBe(200000 - 7500);
});

test('next paycheck across two streams uses earliest', function () {
    $account = checking(100000);
    UserPreference::set('buffer_threshold_cents', 0);

    // His paycheck biweekly Fridays starting 2026-05-08
    ScheduledObligation::create([
        'name' => 'His paycheck',
        'kind' => 'paycheck',
        'direction' => 'inflow',
        'account_id' => $account->id,
        'amount_cents' => 250000,
        'currency' => 'USD',
        'frequency' => 'biweekly',
        'anchor_date' => '2026-05-08',
        'day_of_week' => 5,
    ]);
    // Her paycheck 1st & 15th of month
    ScheduledObligation::create([
        'name' => 'Her paycheck',
        'kind' => 'paycheck',
        'direction' => 'inflow',
        'account_id' => $account->id,
        'amount_cents' => 200000,
        'currency' => 'USD',
        'frequency' => 'semimonthly',
        'anchor_date' => '2026-05-01',
        'day_of_month' => 1,
        'secondary_day_of_month' => 15,
    ]);

    $result = app(SafeToSpendCalculator::class)->compute(
        CarbonImmutable::parse('2026-05-09'),
    );

    // Both '05-15' (her) and '05-22' (his biweekly +14d from 05-08) are upcoming.
    // Next paycheck = 05-15.
    expect($result->horizonEnd->toDateString())->toBe('2026-05-15');
});

test('upcoming obligation reduces safe to spend, paycheck on horizon excluded', function () {
    $account = checking(300000);
    UserPreference::set('buffer_threshold_cents', 0);

    // $1500 paycheck on 05-15 (the horizon — excluded from upcoming inflows)
    ScheduledObligation::create([
        'name' => 'Paycheck',
        'kind' => 'paycheck',
        'direction' => 'inflow',
        'account_id' => $account->id,
        'amount_cents' => 150000,
        'currency' => 'USD',
        'frequency' => 'biweekly',
        'anchor_date' => '2026-05-15',
        'day_of_week' => 5,
    ]);
    // $400 rent due 05-12 (before horizon, an outflow)
    ScheduledObligation::create([
        'name' => 'Rent',
        'kind' => 'bill',
        'direction' => 'outflow',
        'account_id' => $account->id,
        'amount_cents' => 40000,
        'currency' => 'USD',
        'frequency' => 'monthly',
        'anchor_date' => '2026-05-12',
        'day_of_month' => 12,
    ]);

    $result = app(SafeToSpendCalculator::class)->compute(
        CarbonImmutable::parse('2026-05-09'),
    );

    // 300000 (liquid) - 40000 (rent) - 0 (paycheck excluded since it IS horizon) = 260000
    expect($result->safeToSpendCents)->toBe(260000);
});

test('safe-to-spend is negative when liquid cannot cover buffer + obligations', function () {
    $account = checking(10000);
    UserPreference::set('buffer_threshold_cents', 50000);

    ScheduledObligation::create([
        'name' => 'Big Bill',
        'kind' => 'bill',
        'direction' => 'outflow',
        'account_id' => $account->id,
        'amount_cents' => 30000,
        'currency' => 'USD',
        'frequency' => 'monthly',
        'anchor_date' => '2026-05-11',
        'day_of_month' => 11,
    ]);

    $result = app(SafeToSpendCalculator::class)->compute(
        CarbonImmutable::parse('2026-05-09'),
    );

    expect($result->safeToSpendCents)->toBeLessThan(0);
});
