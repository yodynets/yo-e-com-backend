<?php

declare(strict_types = 1);

namespace Yeod\Shared\Domain\Exception;

use RuntimeException;

/**
 * Base class for every exception raised by the Domain layer.
 *
 * Extending a plain SPL exception keeps the Domain free of framework types while
 * still allowing Infrastructure to map domain failures onto HTTP responses.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Machine-readable error code used by API responses and logs.
     */
    abstract public function errorCode(): string;

    /**
     * Additional context safe to expose to clients and logs.
     *
     * @return array<string, scalar|null>
     */
    public function context(): array
    {
        return [];
    }
}
