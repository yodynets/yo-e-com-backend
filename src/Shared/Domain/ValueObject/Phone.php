<?php

declare(strict_types = 1);

namespace Yeod\Shared\Domain\ValueObject;

use Yeod\Shared\Domain\Contracts\ValueObjectInterface;
use Yeod\Shared\Domain\Exception\InvalidValueObject;

/**
 * A phone number stored in E.164 format, for example `+380671234567`.
 *
 * Separators, spaces and brackets are stripped. Ukrainian national numbers
 * (`0XXXXXXXXX`) are upgraded to `+380XXXXXXXXX` because the vast majority of
 * legacy records are stored that way.
 */
final readonly class Phone implements ValueObjectInterface
{
    /**
     * Default calling code applied to national numbers.
     */
    private const string DEFAULT_CALLING_CODE = '380';

    /**
     * @param  string  $value  Already validated E.164 number including the leading `+`.
     */
    private function __construct(public string $value) {}

    /**
     * Create a phone number, returning `null` instead of throwing.
     *
     * @param  string|null  $value  Raw number or `null`.
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
     * Create a phone number from raw user or legacy input.
     *
     * @param  string  $value  Raw number, for example `"(067) 123-45-67"`.
     *
     * @throws InvalidValueObject When the number cannot be normalised to E.164.
     */
    public static function fromString(string $value): self
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            throw InvalidValueObject::because(self::class, 'phone must contain digits');
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = self::DEFAULT_CALLING_CODE.substr($digits, 1);
        }

        if (preg_match('/^[1-9]\d{7,14}$/', $digits) !== 1) {
            throw InvalidValueObject::because(
                self::class,
                sprintf('"%s" is not a valid E.164 phone number', $value),
            );
        }

        return new self('+'.$digits);
    }

    /**
     * Digits only representation, without the leading `+`.
     */
    public function digits(): string
    {
        return substr($this->value, 1);
    }

    /**
     * {@inheritDoc}
     *
     * @param  self  $other  Number to compare against.
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
     * Render the E.164 number.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
