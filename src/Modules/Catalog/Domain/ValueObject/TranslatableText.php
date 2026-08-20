<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Domain\ValueObject;

use ArrayAccess;
use LogicException;
use Yeod\Shared\Domain\Enums\Locale;

/**
 * Mirrors TS: type TranslatableTextRecord = Partial<Record<Locale, string>>
 * Locale resolution ("give me the current locale's value") is not this
 * class's job -- callers pass the Locale explicitly.
 */
final readonly class TranslatableText implements ArrayAccess
{
    /** @param  array<string, string>  $values  Locale value keyed strings */
    public function __construct(private array $values = []) {}

    /** @param  array<string, string>  $data */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function get(Locale $locale, ?Locale $fallback = null): ?string
    {
        return $this->values[$locale->value]
            ?? ($fallback !== null ? $this->values[$fallback->value] ?? null : null);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->values;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): ?string
    {
        return $this->values[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException(self::class.' is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException(self::class.' is immutable.');
    }
}
