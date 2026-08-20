<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\Bus;

/**
 * Marker for a read only request.
 *
 * Queries must never change state. They are named after what they return
 * (`GetProductQuery`, `ListActiveProductsQuery`).
 */
interface Query {}
