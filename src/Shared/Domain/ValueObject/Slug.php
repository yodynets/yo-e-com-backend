<?php

/**
 * @package: mdb-backend
 * @author: Yevhen Odynets
 * @date: 2026-02-08
 * @time: 05:32
 */

declare(strict_types = 1);

namespace Yeod\Shared\Domain\ValueObject;

use Yeod\Shared\Domain\Exception\InvalidValueException;

/**
 * Represents a URL-safe slug.
 *
 * Slugs are lowercase, hyphen-separated identifiers used in URLs and routing.
 * Valid format: lowercase letters, numbers, and hyphens only.
 *
 * Examples:
 * - "master-of-puppets"
 * - "black-album-1991"
 * - "metallica"
 *
 * Note: This VO stores and validates pre-generated slugs for format correctness.
 * Length validation is context-dependent and handled by SlugGenerator based on
 * database column constraints (e.g., 251+4 chars for Artist/Master, 141+4 for Tag/Facet).
 *
 * @extends ValueObject<string>
 */
final readonly class Slug extends ValueObject
{
    protected const string TYPE = 'string';

    /**
     * Check if slug starts with a specific prefix.
     *
     * @param  string  $prefix
     *
     * @return bool
     */
    public function startsWith(string $prefix): bool
    {
        return str_starts_with($this->value, $prefix);
    }

    /**
     * Check if slug ends with a specific suffix.
     *
     * @param  string  $suffix
     *
     * @return bool
     */
    public function endsWith(string $suffix): bool
    {
        return str_ends_with($this->value, $suffix);
    }

    /**
     * Check if slug contains a substring.
     *
     * @param  string  $needle
     *
     * @return bool
     */
    public function contains(string $needle): bool
    {
        return str_contains($this->value, $needle);
    }

    /**
     * Get number of segments in the slug.
     *
     * @return int
     */
    public function segmentCount(): int
    {
        return count($this->segments());
    }

    /**
     * Get slug segments split by hyphens.
     *
     * Example: "master-of-puppets" → ["master", "of", "puppets"]
     *
     * @return array<int, string>
     */
    public function segments(): array
    {
        return explode('-', $this->value);
    }

    /**
     * Validate that the value is a properly formatted slug.
     *
     * Validates format only (lowercase, numbers, hyphens). Length validation
     * is context-dependent and handled by SlugGenerator.
     *
     * @param  mixed  $value  Raw input value
     *
     * @return string         Validated slug
     *
     * @throws InvalidValueException If value is not a valid slug format
     */
    protected function validate(mixed $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw InvalidValueException::emptySlug();
        }

        // Valid slug: lowercase letters, numbers, hyphens
        // Cannot start/end with hyphen, no consecutive hyphens
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $trimmed)) {
            throw InvalidValueException::invalidSlugFormat($trimmed);
        }

        return $trimmed;
    }
}
