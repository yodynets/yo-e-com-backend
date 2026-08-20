<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\Exception;

use LogicException;

/**
 * Thrown when a command or query is dispatched without a registered handler.
 *
 * This is always a wiring mistake: the module service provider forgot to add the
 * mapping to its `commands()` or `queries()` map.
 */
final class HandlerNotRegistered extends LogicException
{
    /**
     * Create the exception for the given message class.
     *
     * @param  class-string  $message  Command or query class that could not be routed.
     */
    public static function for(string $message): self
    {
        return new self(sprintf(
            'No handler registered for [%s]. Add it to the module service provider handler map.',
            $message,
        ));
    }
}
