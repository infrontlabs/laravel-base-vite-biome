<?php

use App\Models\Account;
use App\Models\ScheduledObligation;
use Carbon\CarbonImmutable;

function makeObligation(array $overrides = []): ScheduledObligation
{
    $account = Account::create([
        'name' => 'Test Checking',
        'kind' => 'checking',
        'currency' => 'USD',
    ]);

    return ScheduledObligation::create(array_merge([
        'name' => 'Test',
        'kind' => 'bill',
        'direction' => 'outflow',
        'account_id' => $account->id,
        'amount_cents' => 5000,
        'currency' => 'USD',
        'frequency' => 'monthly',
        'interval' => 1,
        'anchor_date' => '2026-01-15',
    ], $overrides));
}

test('weekly cadence emits 5 occurrences over 30 days', function () {
    $o = makeObligation(['frequency' => 'weekly', 'anchor_date' => '2026-03-01']);

    $occurrences = $o->occurrencesBetween(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-31'),
    );

    expect($occurrences)->toHaveCount(5);
    expect($occurrences->first()->toDateString())->toBe('2026-03-01');
});

test('biweekly Friday paychecks land on Fridays across DST boundary', function () {
    // 2026 DST in US: March 8 (spring forward), Nov 1 (fall back). Use 2026-02-27 (Friday) anchor.
    $o = makeObligation([
        'kind' => 'paycheck',
        'direction' => 'inflow',
        'frequency' => 'biweekly',
        'anchor_date' => '2026-02-27',
        'day_of_week' => 5,
    ]);

    $occurrences = $o->occurrencesBetween(
        CarbonImmutable::parse('2026-02-27'),
        CarbonImmutable::parse('2026-05-31'),
    );

    foreach ($occurrences as $date) {
        expect($date->dayOfWeek)->toBe(5);
    }
    expect($occurrences->count())->toBeGreaterThanOrEqual(6);
});

test('semimonthly 1st and 15th yields both dates each month', function () {
    $o = makeObligation([
        'frequency' => 'semimonthly',
        'anchor_date' => '2026-04-01',
        'day_of_month' => 1,
        'secondary_day_of_month' => 15,
    ]);

    $occurrences = $o
        ->occurrencesBetween(
            CarbonImmutable::parse('2026-04-01'),
            CarbonImmutable::parse('2026-05-31'),
        )
        ->map(fn ($d) => $d->toDateString());

    expect($occurrences->all())->toBe([
        '2026-04-01',
        '2026-04-15',
        '2026-05-01',
        '2026-05-15',
    ]);
});

test('monthly cadence clamps to last day of February in non-leap year', function () {
    $o = makeObligation([
        'frequency' => 'monthly',
        'anchor_date' => '2025-01-31',
        'day_of_month' => 31,
    ]);

    $occurrences = $o
        ->occurrencesBetween(
            CarbonImmutable::parse('2025-01-31'),
            CarbonImmutable::parse('2025-04-30'),
        )
        ->map(fn ($d) => $d->toDateString());

    expect($occurrences->all())->toBe([
        '2025-01-31',
        '2025-02-28',
        '2025-03-31',
        '2025-04-30',
    ]);
});

test('monthly cadence preserves day 29 in leap February', function () {
    $o = makeObligation([
        'frequency' => 'monthly',
        'anchor_date' => '2024-01-29',
        'day_of_month' => 29,
    ]);

    $occurrences = $o
        ->occurrencesBetween(
            CarbonImmutable::parse('2024-01-29'),
            CarbonImmutable::parse('2024-03-31'),
        )
        ->map(fn ($d) => $d->toDateString());

    expect($occurrences->all())->toBe([
        '2024-01-29',
        '2024-02-29',
        '2024-03-29',
    ]);
});

test('annual cadence emits one date per year', function () {
    $o = makeObligation([
        'frequency' => 'annual',
        'anchor_date' => '2026-04-15',
        'day_of_month' => 15,
    ]);

    $occurrences = $o
        ->occurrencesBetween(
            CarbonImmutable::parse('2026-01-01'),
            CarbonImmutable::parse('2028-12-31'),
        )
        ->map(fn ($d) => $d->toDateString());

    expect($occurrences->all())->toBe([
        '2026-04-15',
        '2027-04-15',
        '2028-04-15',
    ]);
});

test('end_date stops recurrence', function () {
    $o = makeObligation([
        'frequency' => 'weekly',
        'anchor_date' => '2026-04-01',
        'end_date' => '2026-04-15',
    ]);

    $occurrences = $o
        ->occurrencesBetween(
            CarbonImmutable::parse('2026-04-01'),
            CarbonImmutable::parse('2026-05-30'),
        )
        ->map(fn ($d) => $d->toDateString());

    expect($occurrences->all())->toBe([
        '2026-04-01',
        '2026-04-08',
        '2026-04-15',
    ]);
});

test('nextOccurrenceAfter is strict', function () {
    $o = makeObligation([
        'frequency' => 'monthly',
        'anchor_date' => '2026-05-10',
        'day_of_month' => 10,
    ]);

    expect($o->nextOccurrenceAfter(CarbonImmutable::parse('2026-05-10'))?->toDateString())
        ->toBe('2026-06-10');
    expect($o->nextOccurrenceAfter(CarbonImmutable::parse('2026-05-09'))?->toDateString())
        ->toBe('2026-05-10');
});
