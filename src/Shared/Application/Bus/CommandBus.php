<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\Bus;

use Yeod\Shared\Application\Exception\HandlerNotRegistered;

/**
 * Dispatches commands to their single registered handler.
 *
 * Presentation code (Filament resources, controllers, console commands) talks to
 * this contract and never instantiates a handler directly.
 */
interface CommandBus
{
    /**
     * Dispatch a command synchronously.
     *
     * @param  Command  $command  Command to dispatch.
     * @return mixed Whatever the handler returns, usually `null` or a new identifier.
     *
     * @throws HandlerNotRegistered When no handler is mapped to the command.
     */
    public function dispatch(Command $command): mixed;
}
