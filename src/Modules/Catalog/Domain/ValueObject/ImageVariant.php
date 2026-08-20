<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Domain\ValueObject;

/**
 * Mirrors TS: interface MetaImage { path?: string; size?: number; width?: number; height?: number }
 */
final readonly class ImageVariant
{
    public function __construct(
        public ?string $path = null,
        public ?int $size = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    /** @param  array{path?: ?string, size?: ?int, width?: ?int, height?: ?int}  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            path  : $data['path'] ?? null,
            size  : $data['size'] ?? null,
            width : $data['width'] ?? null,
            height: $data['height'] ?? null,
        );
    }

    /** @return array{path?: string, size?: int, width?: int, height?: int} */
    public function toArray(): array
    {
        return array_filter([
            'path'   => $this->path,
            'size'   => $this->size,
            'width'  => $this->width,
            'height' => $this->height,
        ], static fn($value) => $value !== null);
    }
}
