<?php

namespace App\Services\Money;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        public int $cents,
        public string $currency = 'USD',
    ) {}

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, $currency);
    }

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDollars(float|string $dollars, string $currency = 'USD'): self
    {
        $cents = (int) round(((float) $dollars) * 100);

        return new self($cents, $currency);
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents - $other->cents, $this->currency);
    }

    public function negate(): self
    {
        return new self(-$this->cents, $this->currency);
    }

    public function abs(): self
    {
        return new self(abs($this->cents), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->cents === $other->cents;
    }

    public function toDollars(): float
    {
        return $this->cents / 100;
    }

    public function format(): string
    {
        $sign = $this->cents < 0 ? '-' : '';
        $abs = number_format(abs($this->cents) / 100, 2, '.', ',');
        $symbol = $this->currency === 'USD' ? '$' : $this->currency.' ';

        return "{$sign}{$symbol}{$abs}";
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}",
            );
        }
    }
}
