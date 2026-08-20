<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\Bus;

/**
 * Handles exactly one query type.
 *
 * @template TQuery of Query
 */
interface QueryHandler
{
    /**
     * Resolve the query into a DTO or a collection of DTOs.
     *
     * Handlers must return Application DTOs, never Domain entities or Eloquent
     * models, so the read side stays decoupled from persistence.
     *
     * @param  TQuery  $query  Query to resolve.
     */
    public function handle(Query $query): mixed;
}
