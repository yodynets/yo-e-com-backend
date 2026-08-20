<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\Bus;

/**
 * Marker for an intent to change state.
 *
 * A command is an immutable DTO named in the imperative mood
 * (`CreateProductCommand`). It carries primitives only, so it can be dispatched
 * from HTTP, Filament, a console command or a queue without translation.
 */
interface Command {}
