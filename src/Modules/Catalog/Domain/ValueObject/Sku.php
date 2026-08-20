<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Domain\ValueObject;

use Yeod\Shared\Domain\Contracts\ValueObjectPrimitive;
use Yeod\Shared\Domain\Exception\InvalidValueObject;

/**
 * Stock keeping unit: the human-readable business identifier of a product.
 *
 * Normalised to uppercase, allows letters, digits, dashes and underscores.
 */
final readonly class Sku implements ValueObjectPrimitive
{
    /**
     * Maximum length accepted by the catalog.
     */
    private const int MAX_LENGTH = 64;

    /**
     * @param  string  $value  Already validated and normalised SKU.
     */
    private function __construct(public string $value) {}

    /**
     * Create a SKU from raw input.
     *
     * @param  string  $value  Raw SKU, for example `" tsh-001 "`.
     *
     * @throws InvalidValueObject When the SKU is empty, too long or contains illegal characters.
     */
    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            throw InvalidValueObject::because(self::class, 'SKU must not be empty');
        }

        if (strlen($normalized) > self::MAX_LENGTH) {
            throw InvalidValueObject::because(
                self::class,
                sprintf('SKU must not exceed %d characters', self::MAX_LENGTH),
            );
        }

        if (preg_match('/^[A-Z0-9][A-Z0-9._\-]*$/', $normalized) !== 1) {
            throw InvalidValueObject::because(
                self::class,
                'SKU may only contain letters, digits, dots, dashes and underscores',
            );
        }

        return new self($normalized);
    }

    /**
     * {@inheritDoc}
     *
     * @param  self  $other  SKU to compare against.
     */
    public function equals(ValueObjectPrimitive $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    /**
     * {@inheritDoc}
     */
    public function toPrimitive(): string
    {
        return $this->value;
    }

    /**
     * Render the normalised SKU.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
