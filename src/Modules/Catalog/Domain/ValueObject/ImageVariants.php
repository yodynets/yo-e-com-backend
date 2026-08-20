<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Domain\ValueObject;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * Mirrors TS: type JsonImageMeta = MetaImage[] | null
 * Uses only core SPL interfaces (ArrayAccess/Countable/IteratorAggregate) --
 * these are PHP core, not Illuminate, so they do not violate the Domain guard.
 */
final readonly class ImageVariants implements ArrayAccess, Countable, IteratorAggregate
{
    /** @param  ImageVariant[]  $variants */
    public function __construct(private array $variants = []) {}

    /** @param  array<int, array<string, mixed>>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            array_map(
                static fn(array $item) => ImageVariant::fromArray($item),
                $data,
            )
        );
    }

    /** Nearest variant by width; caller decides what "nearest" should mean upstream if it matters. */
    public function closestTo(int $width): ?ImageVariant
    {
        if ($this->variants === []) {
            return null;
        }

        $sorted = $this->variants;
        usort(
            $sorted,
            static fn(ImageVariant $a, ImageVariant $b) => abs(($a->width ?? 0) - $width)
                <=> abs(($b->width ?? 0) - $width),
        );

        return $sorted[0];
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(static fn(ImageVariant $variant) => $variant->toArray(), $this->variants);
    }

    public function count(): int
    {
        return count($this->variants);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->variants as $variant) {
            yield $variant;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->variants[$offset]);
    }

    public function offsetGet(mixed $offset): ?ImageVariant
    {
        return $this->variants[$offset] ?? null;
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
