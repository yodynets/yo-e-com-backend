<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use Yeod\Shared\Application\Bus\Query;
use Yeod\Shared\Application\Bus\QueryBus;
use Yeod\Shared\Application\Bus\QueryHandler;
use Yeod\Shared\Application\Exception\HandlerNotRegistered;

/**
 * Query bus that resolves handlers from the Laravel container.
 *
 * Queries are never wrapped in a transaction: they must not write.
 */
final readonly class ContainerQueryBus implements QueryBus
{
    /**
     * @param  Container  $container  Application container used to build handlers.
     * @param  HandlerRegistry  $registry  Query class to handler class map.
     */
    public function __construct(
        private Container $container,
        private HandlerRegistry $registry,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function ask(Query $query): mixed
    {
        $handlerClass = $this->registry->handlerFor($query::class);
        $handler = $this->container->make($handlerClass);

        if (! $handler instanceof QueryHandler) {
            throw HandlerNotRegistered::for($query::class);
        }

        return $handler->handle($query);
    }
}
