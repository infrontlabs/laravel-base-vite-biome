<?php

use App\Models\Account;
use App\Models\User;

test('liabilities subtract from net worth in dashboard payload', function () {
    $this->actingAs(User::factory()->create());

    Account::create([
        'name' => 'Checking',
        'kind' => 'checking',
        'current_balance_cents' => 100000,
        'is_liability' => false,
    ]);
    Account::create([
        'name' => 'Mortgage',
        'kind' => 'mortgage',
        'current_balance_cents' => 250000,
        'is_liability' => true,
    ]);

    $response = $this->get('/dashboard');

    $response->assertOk();
    $netWorth = $response->viewData('page')['props']['netWorthCents'];
    expect($netWorth)->toBe(100000 - 250000);
});

test('inactive accounts are excluded from net worth', function () {
    $this->actingAs(User::factory()->create());

    Account::create([
        'name' => 'Active Checking',
        'kind' => 'checking',
        'current_balance_cents' => 50000,
        'is_active' => true,
    ]);
    Account::create([
        'name' => 'Closed Account',
        'kind' => 'checking',
        'current_balance_cents' => 99999,
        'is_active' => false,
    ]);

    $response = $this->get('/dashboard');

    expect($response->viewData('page')['props']['netWorthCents'])->toBe(50000);
});
