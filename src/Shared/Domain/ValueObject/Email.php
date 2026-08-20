<?php

declare(strict_types = 1);

namespace Yeod\Shared\Domain\ValueObject;

use Yeod\Shared\Domain\Contracts\ValueObjectInterface;
use Yeod\Shared\Domain\Exception\InvalidValueObject;

/**
 * A syntactically valid, normalised email address.
 *
 * Addresses are trimmed and lowercased so that comparison and uniqueness checks
 * behave predictably across modules.
 */
final readonly class Email implements ValueObjectInterface
{
    /**
     * Maximum total length allowed by RFC 5321.
     */
    private const int MAX_LENGTH = 254;

    /**
     * @param  string  $value  Already validated and normalised address.
     */
    private function __construct(public string $value) {}

    /**
     * Create an email address, returning `null` instead of throwing.
     *
     * Useful while importing legacy data where invalid rows must be reported
     * rather than abort the whole batch.
     *
     * @param  string|null  $value  Raw address or `null`.
     */
    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return self::fromString($value);
        } catch (InvalidValueObject) {
            return null;
        }
    }

    /**
     * Create an email address from raw user input.
     *
     * @param  string  $value  Raw address, for example `" John.Doe@Example.COM "`.
     *
     * @throws InvalidValueObject When the address is empty, too long or malformed.
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            throw InvalidValueObject::because(self::class, 'email must not be empty');
        }

        if (strlen($normalized) > self::MAX_LENGTH) {
            throw InvalidValueObject::because(
                self::class,
                sprintf('email must not exceed %d characters', self::MAX_LENGTH),
            );
        }

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidValueObject::because(self::class, sprintf('"%s" is not a valid email address', $value));
        }

        return new self($normalized);
    }

    /**
     * Local part of the address, the segment before the `@`.
     */
    public function localPart(): string
    {
        return substr($this->value, 0, (int)strrpos($this->value, '@'));
    }

    /**
     * Domain part of the address, the segment after the `@`.
     */
    public function domain(): string
    {
        return substr($this->value, (int)strrpos($this->value, '@') + 1);
    }

    /**
     * {@inheritDoc}
     *
     * @param  self  $other  Address to compare against.
     */
    public function equals(ValueObjectInterface $other): bool
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
     * Render the normalised address.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
