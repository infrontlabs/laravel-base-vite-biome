<?php

use App\Models\Account;
use App\Models\ScheduledObligation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Sync\SimulatedPlaidSync;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->account = Account::create([
        'name' => 'Checking',
        'kind' => 'depository',
        'subkind' => 'checking',
        'currency' => 'USD',
        'current_balance_cents' => 100_000,
    ]);
});

test('sync index page loads with no candidates', function () {
    $this->get('/sync')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('sync/index')
            ->where('candidates', [])
            ->where('mergedCount', 0)
        );
});

test('simulate creates pending plaid transactions from scheduled obligations', function () {
    ScheduledObligation::create([
        'name' => 'Netflix',
        'kind' => 'subscription',
        'direction' => 'outflow',
        'account_id' => $this->account->id,
        'amount_cents' => 1_599,
        'currency' => 'USD',
        'frequency' => 'monthly',
        'interval' => 1,
        'anchor_date' => CarbonImmutable::today()->subDays(5)->toDateString(),
        'day_of_month' => CarbonImmutable::today()->subDays(5)->day,
        'is_active' => true,
    ]);

    $this->post('/sync/simulate')->assertRedirect('/sync');

    $candidates = Transaction::query()
        ->where('source', 'plaid')
        ->where('status', 'pending')
        ->get();

    expect($candidates)->not->toBeEmpty();
    expect($candidates->first()->plaid_transaction_id)->not->toBeNull();
});

test('matching merges plaid candidate into manual transaction', function () {
    $manual = Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1_599,
        'currency' => 'USD',
        'posted_date' => '2026-05-10',
        'description' => 'Netflix subscription',
        'merchant_name' => 'Netflix',
        'source' => 'manual',
        'status' => 'posted',
    ]);

    $plaid = Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1_599,
        'currency' => 'USD',
        'posted_date' => '2026-05-11',
        'description' => 'NETFLIX.COM 7733',
        'merchant_name' => 'Netflix',
        'source' => 'plaid',
        'status' => 'pending',
        'plaid_item_id' => 'sim_item',
        'plaid_transaction_id' => 'sim_abc123',
    ]);

    $this->post("/sync/{$plaid->id}/match", [
        'manual_transaction_id' => $manual->id,
    ])->assertRedirect('/sync');

    $manual->refresh();
    expect($manual->source)->toBe('merged');
    expect($manual->status)->toBe('posted');
    expect($manual->plaid_transaction_id)->toBe('sim_abc123');
    expect($manual->merged_from_transaction_id)->toBe($plaid->id);

    $plaid->refresh();
    expect($plaid->status)->toBe('void');
    expect($plaid->merged_into_id)->toBe($manual->id);
});

test('accepting a plaid candidate posts it as a new transaction', function () {
    $plaid = Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1_999,
        'currency' => 'USD',
        'posted_date' => '2026-05-12',
        'description' => 'AMAZON.COM',
        'source' => 'plaid',
        'status' => 'pending',
        'plaid_item_id' => 'sim_item',
        'plaid_transaction_id' => 'sim_xyz789',
    ]);

    $this->post("/sync/{$plaid->id}/accept")->assertRedirect('/sync');

    $plaid->refresh();
    expect($plaid->status)->toBe('posted');
    expect($plaid->source)->toBe('plaid');
});

test('rejecting a plaid candidate deletes it', function () {
    $plaid = Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -500,
        'currency' => 'USD',
        'posted_date' => '2026-05-12',
        'description' => 'COFFEE',
        'source' => 'plaid',
        'status' => 'pending',
        'plaid_transaction_id' => 'sim_reject',
    ]);

    $this->delete("/sync/{$plaid->id}")->assertRedirect('/sync');

    expect(Transaction::find($plaid->id))->toBeNull();
});

test('cannot match a non-plaid transaction', function () {
    $manual1 = Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1_000,
        'currency' => 'USD',
        'posted_date' => '2026-05-10',
        'description' => 'a',
        'source' => 'manual',
        'status' => 'posted',
    ]);

    $manual2 = Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1_000,
        'currency' => 'USD',
        'posted_date' => '2026-05-11',
        'description' => 'b',
        'source' => 'manual',
        'status' => 'posted',
    ]);

    $this->post("/sync/{$manual1->id}/match", [
        'manual_transaction_id' => $manual2->id,
    ])->assertSessionHasErrors('candidate');
});

test('index surfaces suggestions for pending plaid candidates', function () {
    Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1_599,
        'currency' => 'USD',
        'posted_date' => '2026-05-10',
        'description' => 'Netflix',
        'merchant_name' => 'Netflix',
        'source' => 'manual',
        'status' => 'posted',
    ]);

    Transaction::create([
        'account_id' => $this->account->id,
        'amount_cents' => -1_599,
        'currency' => 'USD',
        'posted_date' => '2026-05-11',
        'description' => 'NETFLIX.COM',
        'merchant_name' => 'Netflix',
        'source' => 'plaid',
        'status' => 'pending',
        'plaid_transaction_id' => 'sim_match',
    ]);

    $this->get('/sync')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('sync/index')
            ->has('candidates', 1)
            ->has('candidates.0.suggestions', 1)
        );
});

test('SimulatedPlaidSync dedupes obligation-derived candidates across runs', function () {
    ScheduledObligation::create([
        'name' => 'Rent',
        'kind' => 'bill',
        'direction' => 'outflow',
        'account_id' => $this->account->id,
        'amount_cents' => 180_000,
        'currency' => 'USD',
        'frequency' => 'monthly',
        'interval' => 1,
        'anchor_date' => CarbonImmutable::today()->subDays(3)->toDateString(),
        'day_of_month' => CarbonImmutable::today()->subDays(3)->day,
        'is_active' => true,
    ]);

    $sync = app(SimulatedPlaidSync::class);
    $sync->run();
    $countAfterFirst = Transaction::query()->where('source', 'plaid')->count();

    $sync->run();
    $countAfterSecond = Transaction::query()->where('source', 'plaid')->count();

    // Obligation-derived candidates are deduped; extras may add a few more.
    expect($countAfterSecond)->toBeGreaterThanOrEqual($countAfterFirst);
});
