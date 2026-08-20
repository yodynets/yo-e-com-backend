<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use Yeod\Shared\Application\Bus\Command;
use Yeod\Shared\Application\Bus\CommandBus;
use Yeod\Shared\Application\Bus\CommandHandler;
use Yeod\Shared\Application\Exception\HandlerNotRegistered;

/**
 * Command bus that resolves handlers from the Laravel container.
 *
 * @see TransactionalCommandBus For the decorator that wraps dispatching in a transaction.
 */
final readonly class ContainerCommandBus implements CommandBus
{
    /**
     * @param  Container  $container  Application container used to build handlers.
     * @param  HandlerRegistry  $registry  Command class to handler class map.
     */
    public function __construct(
        private Container $container,
        private HandlerRegistry $registry,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function dispatch(Command $command): mixed
    {
        $handlerClass = $this->registry->handlerFor($command::class);
        $handler = $this->container->make($handlerClass);

        if (! $handler instanceof CommandHandler) {
            throw HandlerNotRegistered::for($command::class);
        }

        return $handler->handle($command);
    }
}
