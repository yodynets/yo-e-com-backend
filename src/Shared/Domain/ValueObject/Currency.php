<?php

declare(strict_types = 1);

namespace Yeod\Shared\Domain\ValueObject;

use Yeod\Shared\Domain\Exception\InvalidValueObject;

/**
 * ISO 4217 currencies supported by the platform.
 *
 * Add a case here (and only here) when the business starts selling in a new
 * currency. Keeping it an enum makes an unsupported currency unrepresentable.
 */
enum Currency: string
{
    case UAH = 'UAH';
    case USD = 'USD';
    case EUR = 'EUR';
    case PLN = 'PLN';
    case GBP = 'GBP';

    /**
     * Create a currency from a case-insensitive ISO 4217 alphabetic code.
     *
     * @param  string  $code  Three letter currency code, for example `uah`.
     *
     * @throws InvalidValueObject When the code is not supported.
     */
    public static function fromCode(string $code): self
    {
        $normalized = strtoupper(trim($code));

        return self::tryFrom($normalized)
            ?? throw InvalidValueObject::because(self::class, sprintf('unsupported currency code "%s"', $code));
    }

    /**
     * Number of minor units contained in one major unit, for example 100 cents.
     */
    public function minorUnitFactor(): int
    {
        return 10 ** $this->minorUnitScale();
    }

    /**
     * Number of decimal digits used by the currency (the minor unit exponent).
     */
    public function minorUnitScale(): int
    {
        return 2;
    }
}
