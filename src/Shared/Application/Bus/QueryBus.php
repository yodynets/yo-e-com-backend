<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\Bus;

use Yeod\Shared\Application\Exception\HandlerNotRegistered;

/**
 * Dispatches queries to their single registered handler.
 */
interface QueryBus
{
    /**
     * Ask a query and return its result.
     *
     * @param  Query  $query  Query to ask.
     *
     * @throws HandlerNotRegistered When no handler is mapped to the query.
     */
    public function ask(Query $query): mixed;
}
