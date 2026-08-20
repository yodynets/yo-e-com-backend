<?php

declare(strict_types = 1);

namespace Yeod\Shared\Domain\ValueObject;

use Yeod\Shared\Domain\Contracts\ValueObjectPrimitive;
use Yeod\Shared\Domain\Exception\CurrencyMismatch;
use Yeod\Shared\Domain\Exception\InvalidValueObject;

/**
 * A monetary amount stored as an integer number of minor units.
 *
 * Floats are never used for money. `Money::fromMinor(1999, Currency::UAH)`
 * represents 19.99 UAH. All arithmetic returns a new instance, so an amount can
 * safely be shared between aggregates.
 */
final readonly class Money implements ValueObjectPrimitive
{
    /**
     * @param  int  $minorAmount  Amount expressed in minor units (kopiykas, cents).
     * @param  Currency  $currency  Currency of the amount.
     */
    private function __construct(
        public int $minorAmount,
        public Currency $currency,
    ) {}

    /**
     * Create money from an integer number of minor units.
     *
     * @param  int  $minorAmount  Amount expressed in minor units, may be negative.
     * @param  Currency  $currency  Currency of the amount.
     */
    public static function fromMinor(int $minorAmount, Currency $currency): self
    {
        return new self($minorAmount, $currency);
    }

    /**
     * Create money from a decimal string such as `"19.99"`.
     *
     * A string (not a float) is required on purpose to avoid binary rounding
     * errors. The number of decimals must not exceed the currency scale.
     *
     * @param  string  $amount  Decimal amount, for example `"-1250.00"`.
     * @param  Currency  $currency  Currency of the amount.
     *
     * @throws InvalidValueObject When the string is not a valid decimal amount.
     */
    public static function fromDecimalString(string $amount, Currency $currency): self
    {
        $normalized = str_replace([' ', ','], ['', '.'], trim($amount));
        $scale = $currency->minorUnitScale();

        if (preg_match('/^-?\d+(\.\d+)?$/', $normalized) !== 1) {
            throw InvalidValueObject::because(self::class, sprintf('"%s" is not a decimal amount', $amount));
        }

        [$integerPart, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');

        if (strlen($fraction) > $scale) {
            throw InvalidValueObject::because(
                self::class,
                sprintf('%s allows at most %d decimal digits', $currency->value, $scale),
            );
        }

        $isNegative = str_starts_with($integerPart, '-');
        $digits = ltrim($integerPart, '-').str_pad($fraction, $scale, '0');
        $minor = (int)$digits;

        return new self($isNegative ? -$minor : $minor, $currency);
    }

    /**
     * Create a zero amount in the given currency.
     *
     * @param  Currency  $currency  Currency of the amount.
     */
    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    /**
     * Add another amount of the same currency.
     *
     * @param  self  $other  Amount to add.
     *
     * @throws CurrencyMismatch When currencies differ.
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorAmount + $other->minorAmount, $this->currency);
    }

    /**
     * Guard that both operands share the same currency.
     *
     * @param  self  $other  Right hand operand.
     *
     * @throws CurrencyMismatch When currencies differ.
     */
    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatch($this->currency, $other->currency);
        }
    }

    /**
     * Subtract another amount of the same currency.
     *
     * @param  self  $other  Amount to subtract.
     *
     * @throws CurrencyMismatch When currencies differ.
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorAmount - $other->minorAmount, $this->currency);
    }

    /**
     * Multiply the amount by an integer quantity, for example an order line.
     *
     * @param  int  $quantity  Multiplier, must not be negative.
     *
     * @throws InvalidValueObject When the quantity is negative.
     */
    public function multiplyBy(int $quantity): self
    {
        if ($quantity < 0) {
            throw InvalidValueObject::because(self::class, 'quantity must not be negative');
        }

        return new self($this->minorAmount * $quantity, $this->currency);
    }

    /**
     * Apply a percentage and round half up, for example VAT or a discount.
     *
     * @param  float  $percent  Percentage to apply, for example `20.0` for 20%.
     */
    public function percentage(float $percent): self
    {
        return new self((int)round($this->minorAmount * $percent / 100), $this->currency);
    }

    /**
     * Return the amount with the opposite sign.
     */
    public function negate(): self
    {
        return new self(-$this->minorAmount, $this->currency);
    }

    /**
     * Determine whether this amount is greater than the given one.
     *
     * @param  self  $other  Amount to compare against.
     *
     * @throws CurrencyMismatch When currencies differ.
     */
    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * Compare two amounts of the same currency.
     *
     * @param  self  $other  Amount to compare against.
     *
     * @return int Negative when this amount is smaller, 0 when equal, positive when greater.
     *
     * @throws CurrencyMismatch When currencies differ.
     */
    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->minorAmount <=> $other->minorAmount;
    }

    /**
     * Determine whether this amount is less than the given one.
     *
     * @param  self  $other  Amount to compare against.
     *
     * @throws CurrencyMismatch When currencies differ.
     */
    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * Determine whether the amount is exactly zero.
     */
    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    /**
     * Determine whether the amount is negative.
     */
    public function isNegative(): bool
    {
        return $this->minorAmount < 0;
    }

    /**
     * Determine whether the amount is positive.
     */
    public function isPositive(): bool
    {
        return $this->minorAmount > 0;
    }

    /**
     * {@inheritDoc}
     *
     * @param  self  $other  Amount to compare against.
     */
    public function equals(ValueObjectPrimitive $other): bool
    {
        return $this->minorAmount === $other->minorAmount
            && $this->currency === $other->currency;
    }

    /**
     * {@inheritDoc}
     */
    public function toPrimitive(): int
    {
        return $this->minorAmount;
    }

    /**
     * Render the amount together with its currency code, for example `19.99 UAH`.
     */
    public function __toString(): string
    {
        return $this->toDecimalString().' '.$this->currency->value;
    }

    /**
     * Render the amount as a plain decimal string, for example `"19.99"`.
     */
    public function toDecimalString(): string
    {
        $scale = $this->currency->minorUnitScale();
        $sign = $this->minorAmount < 0 ? '-' : '';
        $absolute = (string)abs($this->minorAmount);
        $padded = str_pad($absolute, $scale + 1, '0', STR_PAD_LEFT);

        return $sign.substr($padded, 0, -$scale).'.'.substr($padded, -$scale);
    }
}
