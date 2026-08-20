<?php

/**
 * @package: mdb-backend
 * @author: Yevhen Odynets
 * @date: 2026-02-27
 * @time: 11:50
 */

declare(strict_types = 1);

namespace Yeod\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Yeod\Shared\Domain\Contracts\ValueObjectInterface;

/**
 * Base class for all Value Objects.
 *
 * @template T
 * @implements ValueObjectInterface<T>
 */
abstract readonly class ValueObject implements ValueObjectInterface
{

    /**
     * Expected type for validation.
     * Override in child classes: 'string', 'int', 'float', 'bool', 'array', etc.
     */
    protected const string TYPE = 'mixed';

    /**
     * @var T
     */
    protected mixed $value;

    /**
     * @param  T  $value
     */
    protected function __construct(
        mixed $value
    ) {
        $this->validateType($value);
        $this->value = $this->validate($value);
    }

    /**
     * Validate runtime type matches expected TYPE constant.
     */
    private function validateType(mixed $value): void
    {
        $expectedType = static::TYPE;

        if ($expectedType !== 'mixed') {
            $actualType = get_debug_type($value);

            if ($actualType !== $expectedType) {
                throw new InvalidArgumentException(
                    sprintf(
                        '%s expects %s, got %s',
                        static::class,
                        $expectedType,
                        $actualType
                    )
                );
            }
        }
    }

    /**
     * Validate and optionally normalise the value.
     *
     * @param  T  $value
     *
     * @return T Normalized value
     * @throws InvalidArgumentException
     */
    abstract protected function validate(mixed $value): mixed;

    /**
     * @inheritDoc
     */
    public static function fromNullable(mixed $value): ?static
    {
        if ($value === null) {
            return null;
        }

        // For string - checking if empty
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return static::from($value);
    }

    /**
     * @inheritDoc
     */
    public static function from(mixed $value): static
    {
        return new static($value);
    }

    /**
     * @inheritDoc
     */
    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function equals(self|ValueObjectInterface $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }
}
