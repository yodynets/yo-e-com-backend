<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Domain\ValueObject;

/**
 * Interface MetaLocale { title?: string; description?: string; keywords?: string[] }
 */
final readonly class MetaLocale
{
    /** @param  string[]  $keywords */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public array $keywords = [],
    ) {}

    /** @param  array{title?: ?string, description?: ?string, keywords?: string[]}  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            title      : $data['title'] ?? null,
            description: $data['description'] ?? null,
            keywords   : $data['keywords'] ?? [],
        );
    }

    /** @return array{title?: string, description?: string, keywords?: string[]} */
    public function toArray(): array
    {
        return array_filter([
            'title'       => $this->title,
            'description' => $this->description,
            'keywords'    => $this->keywords,
        ], static fn($value) => $value !== null && $value !== []);
    }
}
