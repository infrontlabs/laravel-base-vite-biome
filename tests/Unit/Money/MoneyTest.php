<?php

use App\Services\Money\Money;

test('fromDollars rounds to nearest cent', function () {
    expect(Money::fromDollars(12.345)->cents)->toBe(1235);
    expect(Money::fromDollars(12.344)->cents)->toBe(1234);
    expect(Money::fromDollars(-1.005)->cents)->toBe(-100); // halves round per PHP's standard rounding
});

test('plus and minus respect sign', function () {
    $a = Money::fromCents(500);
    $b = Money::fromCents(150);

    expect($a->plus($b)->cents)->toBe(650);
    expect($a->minus($b)->cents)->toBe(350);
    expect($b->minus($a)->cents)->toBe(-350);
});

test('plus across mismatched currencies throws', function () {
    Money::fromCents(100, 'USD')->plus(Money::fromCents(100, 'EUR'));
})->throws(InvalidArgumentException::class);

test('format renders sign, dollars, cents, and grouping', function () {
    expect(Money::fromCents(0)->format())->toBe('$0.00');
    expect(Money::fromCents(1234567)->format())->toBe('$12,345.67');
    expect(Money::fromCents(-100)->format())->toBe('-$1.00');
});

test('helpers report sign correctly', function () {
    expect(Money::zero()->isZero())->toBeTrue();
    expect(Money::fromCents(-1)->isNegative())->toBeTrue();
    expect(Money::fromCents(1)->isPositive())->toBeTrue();
});
