<?php

declare(strict_types=1);

namespace Yeod\Tests\Feature\Shared;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Yeod\Modules\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use Yeod\Modules\Catalog\Application\Command\CreateProduct\CreateProductHandler;
use Yeod\Shared\Application\Bus\CommandBus;
use Yeod\Shared\Application\Bus\QueryBus;
use Yeod\Shared\Application\Exception\HandlerNotRegistered;
use Yeod\Shared\Domain\Clock\Clock;
use Yeod\Shared\Domain\Event\DomainEventDispatcher;
use Yeod\Shared\Infrastructure\Bus\HandlerRegistry;
use Yeod\Shared\Infrastructure\Module\ModuleRegistry;
use Yeod\Tests\TestCase;

/**
 * Guards the registration convention: a module contributes its handlers, Filament
 * resources and migrations through its own provider only.
 */
#[CoversNothing]
final class ModuleRegistrationTest extends TestCase
{
    #[Test]
    public function the_shared_kernel_ports_are_bound(): void
    {
        foreach ([Clock::class, CommandBus::class, QueryBus::class, DomainEventDispatcher::class] as $port) {
            self::assertTrue($this->app->bound($port), $port.' is not bound.');
        }
    }

    #[Test]
    public function modules_register_their_handlers(): void
    {
        $registry = $this->app->make(HandlerRegistry::class);

        self::assertSame(CreateProductHandler::class, $registry->handlerFor(CreateProductCommand::class));
    }

    #[Test]
    public function an_unmapped_message_fails_loudly(): void
    {
        $this->expectException(HandlerNotRegistered::class);

        $this->app->make(HandlerRegistry::class)->handlerFor('App\\Nope');
    }

    #[Test]
    public function modules_announce_themselves_to_the_registry(): void
    {
        self::assertContains('catalog', $this->app->make(ModuleRegistry::class)->names());
    }

    #[Test]
    public function module_migrations_are_loaded_from_inside_the_module(): void
    {
        $paths = $this->app->make('migrator')->paths();

        self::assertNotEmpty(array_filter(
            $paths,
            static fn (string $path): bool => str_contains($path, 'Modules'.DIRECTORY_SEPARATOR.'Catalog'),
        ));
    }
}
