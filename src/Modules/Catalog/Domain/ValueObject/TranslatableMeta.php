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
 * Mirrors TS: type TranslatableMeta = Partial<Record<Locale, MetaLocale>> | null
 */
final readonly class TranslatableMeta implements ArrayAccess
{
    /** @param  array<string, MetaLocale>  $values */
    public function __construct(private array $values = []) {}

    /** @param  array<string, array<string, mixed>>  $data */
    public static function fromArray(array $data): self
    {
        $values = array_map(static function ($meta) { return MetaLocale::fromArray($meta ?? []); }, $data);

        return new self($values);
    }

    public function get(Locale $locale, ?Locale $fallback = null): ?MetaLocale
    {
        return $this->values[$locale->value]
            ?? ($fallback !== null ? $this->values[$fallback->value] ?? null : null);
    }

    /** @return array<string, array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(static fn(MetaLocale $meta) => $meta->toArray(), $this->values);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): ?MetaLocale
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
