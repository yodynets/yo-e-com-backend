<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Bus;

use Yeod\Shared\Application\Exception\HandlerNotRegistered;

/**
 * Message class to handler class map, filled by module service providers.
 *
 * Keeping the map in one singleton means a module only has to declare its own
 * handlers; nothing central needs to be edited when a module is copied into
 * another project.
 */
final class HandlerRegistry
{
    /**
     * @var array<class-string, class-string>
     */
    private array $map = [];

    /**
     * Register a batch of message to handler mappings.
     *
     * @param  array<class-string, class-string>  $mappings  Message class => handler class.
     */
    public function registerMany(array $mappings): void
    {
        foreach ($mappings as $message => $handler) {
            $this->register($message, $handler);
        }
    }

    /**
     * Register a single message to handler mapping.
     *
     * @param  class-string  $message  Command or query class.
     * @param  class-string  $handler  Handler class resolved from the container.
     */
    public function register(string $message, string $handler): void
    {
        $this->map[$message] = $handler;
    }

    /**
     * Resolve the handler class registered for the given message.
     *
     * @param  class-string  $message  Command or query class.
     * @return class-string Handler class name.
     *
     * @throws HandlerNotRegistered When the message has no handler.
     */
    public function handlerFor(string $message): string
    {
        return $this->map[$message] ?? throw HandlerNotRegistered::for($message);
    }

    /**
     * Whole map, mainly useful for diagnostics and architecture tests.
     *
     * @return array<class-string, class-string>
     */
    public function all(): array
    {
        return $this->map;
    }
}
