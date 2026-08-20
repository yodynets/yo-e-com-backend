<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\Bus;

/**
 * Handles exactly one command type.
 *
 * @template TCommand of Command
 */
interface CommandHandler
{
    /**
     * Execute the use case described by the command.
     *
     * A handler either completes the whole use case or throws. Returning the
     * identifier of a freshly created aggregate is allowed; returning read
     * models is not, use a query for that.
     *
     * @param  TCommand  $command  Command to execute.
     */
    public function handle(Command $command): mixed;
}
