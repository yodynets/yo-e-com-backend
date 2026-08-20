<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Bus;

use Illuminate\Database\ConnectionResolverInterface;
use Yeod\Shared\Application\Bus\Command;
use Yeod\Shared\Application\Bus\CommandBus;

/**
 * Decorator that runs every command inside a single database transaction.
 *
 * One command equals one use case equals one transaction. Handlers therefore never
 * open transactions themselves, and nested dispatching reuses the outer one.
 */
final readonly class TransactionalCommandBus implements CommandBus
{
    /**
     * @param  CommandBus  $inner  Bus that actually resolves and calls the handler.
     * @param  ConnectionResolverInterface  $connections  Database connection resolver.
     */
    public function __construct(
        private CommandBus $inner,
        private ConnectionResolverInterface $connections,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function dispatch(Command $command): mixed
    {
        return $this->connections->connection()->transaction(
            fn (): mixed => $this->inner->dispatch($command),
        );
    }
}
