<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->account = Account::create([
        'name' => 'Checking',
        'kind' => 'checking',
        'currency' => 'USD',
        'current_balance_cents' => 100000,
    ]);
});

test('can create a manual pending transaction', function () {
    $response = $this->post('/transactions', [
        'account_id' => $this->account->id,
        'amount' => -42.50,
        'date' => '2026-05-10',
        'status' => 'pending',
        'description' => 'Test coffee',
    ]);

    $response->assertRedirect('/transactions');
    expect(Transaction::count())->toBe(1);

    $tx = Transaction::first();
    expect($tx->amount_cents)->toBe(-4250);
    expect($tx->status)->toBe('pending');
    expect($tx->pending_date?->toDateString())->toBe('2026-05-10');
    expect($tx->posted_date)->toBeNull();
    expect($tx->source)->toBe('manual');
});

test('posted transactions write posted_date, not pending_date', function () {
    $this->post('/transactions', [
        'account_id' => $this->account->id,
        'amount' => 100,
        'date' => '2026-05-10',
        'status' => 'posted',
        'description' => 'Refund',
    ]);

    $tx = Transaction::first();
    expect($tx->posted_date?->toDateString())->toBe('2026-05-10');
    expect($tx->pending_date)->toBeNull();
});

test('cannot create transaction without required fields', function () {
    $response = $this->post('/transactions', []);
    $response->assertSessionHasErrors(['account_id', 'amount', 'date', 'status', 'description']);
});

test('can update and delete a transaction', function () {
    $tx = Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1000,
        'currency' => 'USD',
        'pending_date' => '2026-05-09',
        'description' => 'old',
        'source' => 'manual',
        'status' => 'pending',
    ]);

    $this->patch("/transactions/{$tx->id}", [
        'amount' => -20,
        'date' => '2026-05-10',
        'status' => 'pending',
        'description' => 'new',
    ]);

    $tx->refresh();
    expect($tx->amount_cents)->toBe(-2000);
    expect($tx->description)->toBe('new');

    $this->delete("/transactions/{$tx->id}");
    expect(Transaction::count())->toBe(0);
});
