<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\ServiceProvider;
use Yeod\Shared\Application\Bus\CommandBus;
use Yeod\Shared\Application\Bus\QueryBus;
use Yeod\Shared\Domain\Clock\Clock;
use Yeod\Shared\Domain\Event\DomainEventDispatcher;
use Yeod\Shared\Infrastructure\Bus\ContainerCommandBus;
use Yeod\Shared\Infrastructure\Bus\ContainerQueryBus;
use Yeod\Shared\Infrastructure\Bus\HandlerRegistry;
use Yeod\Shared\Infrastructure\Bus\TransactionalCommandBus;
use Yeod\Shared\Infrastructure\Clock\SystemClock;
use Yeod\Shared\Infrastructure\Event\LaravelDomainEventDispatcher;
use Yeod\Shared\Infrastructure\Module\ModuleRegistry;

/**
 * Wires the Shared Kernel ports to their Laravel implementations.
 *
 * This provider must be registered before any module provider, because modules
 * push their handler maps into the `HandlerRegistry` defined here.
 */
final class SharedServiceProvider extends ServiceProvider
{
    /**
     * Register Shared Kernel services.
     */
    public function register(): void
    {
        $this->app->singleton(HandlerRegistry::class);
        $this->app->singleton(ModuleRegistry::class);

        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(DomainEventDispatcher::class, LaravelDomainEventDispatcher::class);

        $this->app->singleton(QueryBus::class, static fn (Container $app): QueryBus => new ContainerQueryBus(
            $app,
            $app->make(HandlerRegistry::class),
        ));

        $this->app->singleton(CommandBus::class, static fn (Container $app): CommandBus => new TransactionalCommandBus(
            new ContainerCommandBus($app, $app->make(HandlerRegistry::class)),
            $app->make(ConnectionResolverInterface::class),
        ));
    }

    /**
     * Services provided by this provider, used for deferred resolution hints.
     *
     * @return list<class-string>
     */
    public function provides(): array
    {
        return [
            Clock::class,
            CommandBus::class,
            DomainEventDispatcher::class,
            HandlerRegistry::class,
            ModuleRegistry::class,
            QueryBus::class,
        ];
    }
}
