<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Exception;

use Yeod\Shared\Domain\ValueObject\Currency;

/**
 * Thrown when two monetary amounts of different currencies are combined.
 */
final class CurrencyMismatch extends DomainException
{
    /**
     * @param  Currency  $expected  Currency of the left hand operand.
     * @param  Currency  $actual  Currency of the right hand operand.
     */
    public function __construct(
        private readonly Currency $expected,
        private readonly Currency $actual,
    ) {
        parent::__construct(sprintf(
            'Cannot operate on money of different currencies: %s and %s.',
            $expected->value,
            $actual->value,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode(): string
    {
        return 'shared.currency_mismatch';
    }

    /**
     * {@inheritDoc}
     */
    public function context(): array
    {
        return [
            'expected' => $this->expected->value,
            'actual' => $this->actual->value,
        ];
    }
}
