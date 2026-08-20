<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Exception;

/**
 * Thrown when a value object receives input that violates its invariants.
 */
final class InvalidValueObject extends DomainException
{
    /**
     * @param  string  $valueObject  Fully qualified value object name.
     * @param  string  $reason  Human readable explanation of the violation.
     */
    private function __construct(
        private readonly string $valueObject,
        private readonly string $reason,
    ) {
        parent::__construct(sprintf('%s is invalid: %s', self::shortName($valueObject), $reason));
    }

    /**
     * Create the exception for a given value object class and reason.
     *
     * @param  string  $valueObject  Fully qualified value object name, usually `self::class`.
     * @param  string  $reason  Human readable explanation of the violation.
     */
    public static function because(string $valueObject, string $reason): self
    {
        return new self($valueObject, $reason);
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode(): string
    {
        return 'shared.invalid_value_object';
    }

    /**
     * {@inheritDoc}
     */
    public function context(): array
    {
        return [
            'value_object' => $this->valueObject,
            'reason' => $this->reason,
        ];
    }

    /**
     * Return the short class name without reaching for framework helpers.
     *
     * @param  string  $class  Fully qualified class name.
     */
    private static function shortName(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
