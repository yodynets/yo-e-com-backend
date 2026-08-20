<?php

/**
 * @package: mdb-backend
 * @author: Yevhen Odynets
 * @date: 2026-03-07
 * @time: 07:49
 */

declare(strict_types = 1);

namespace Yeod\Shared\Domain\Exception;

use InvalidArgumentException;

final class InvalidValueException extends InvalidArgumentException
{
    public static function invalidTimeFormat(string $value): self
    {
        return new self(
            "Invalid duration format: \"{$value}\". Expected HH:MM:SS (H:i:s) format between 00:00:00 and 23:59:59."
        );
    }

    public static function invalidTimezoneFormat(string $value): self
    {
        return new self(
            "Invalid timezone format: \"{$value}\"."
        );
    }

    public static function invalidEan13Format(string $value): self
    {
        return new self("Invalid EAN-13 catalog number (MCN) as a plain string value (yet normalized): {$value}");
    }

    public static function invalidCurrencyFormat(string $value): self
    {
        return new self(
            "Invalid currency codes are standardized 3-letter as a plain string value (yet normalized): {$value}"
        );
    }

    public static function invalidLocaleFormat(string $value): self
    {
        return new self(
            "Invalid locale code as a plain string value (yet normalized): {$value}"
        );
    }

    public static function invalidEmailFormat(string $value): self
    {
        return new self("Invalid email address format (yet normalized): {$value}");
    }

    public static function invalidDomainFormat(string $value): self
    {
        return new self("Invalid domain address format (yet normalized): {$value}");
    }

    public static function invalidBCryptHashFormat(): self
    {
        return new self("Value must be a valid bcrypt or argon2 password hash.");
    }

    public static function invalidPasswordLength(int $minLength, int $length): self
    {
        return new self("Password must be at least $minLength characters, got $length.");
    }

    public static function invalidId(mixed $value): self
    {
        return new self("ID must be a positive integer (>= 1), got: {$value}.");
    }

    public static function invalidIsrcFormat(string $value): self
    {
        return new self(
            "Invalid ISRC format: \"{$value}\". Expected format: CC-XXX-YY-NNNNN (12 characters)."
        );
    }

    public static function invalidCountryCode(string $value): self
    {
        return new self("Invalid country code: \"{$value}\". Expected ISO 3166-1 alpha-2 format (2 letters).");
    }

    public static function invalidYear(int $value, int $min, int $max): self
    {
        return new self("Year must be between {$min} and {$max}, got: {$value}.");
    }

    public static function emptySlug(): self
    {
        return new self('Slug cannot be empty.');
    }

    public static function invalidSlugFormat(string $slug): self
    {
        return new self(
            "Invalid slug format: \"{$slug}\". Must be lowercase letters, numbers, and hyphens only."
        );
    }

    public static function invalidUrl(string $value): self
    {
        return new self("Invalid URL format: \"{$value}\".");
    }

    public static function urlMustBeHttpOrHttps(string $value): self
    {
        return new self("URL must use http or https scheme: \"{$value}\".");
    }

    public static function invalidUrlScheme(string $scheme): self
    {
        return new self("Invalid URL scheme: \"{$scheme}\". Must be http or https.");
    }

    public static function invalidUuid(mixed $value): self
    {
        return new self("Invalid UUID format: \"{$value}\".");
    }

    public static function invalidExtendedCountryCode(string $value): self
    {
        return new self(
            "Invalid extended country code: \"{$value}\". Expected 2 uppercase letters."
        );
    }

    public static function invalidDate(string $value, string $format): self
    {
        return new self(
            "Invalid date: \"{$value}\". Expected format: {$format}."
        );
    }

    public static function invalidDateRange(string $value, string $min, string $max): self
    {
        return new self(
            "Date \"{$value}\" is out of valid range [{$min}, {$max}]."
        );
    }

    public static function domainDoesNotResolve(mixed $value): self
    {
        return new self("Domain: \"{$value}\" does not resolved");
    }
}
