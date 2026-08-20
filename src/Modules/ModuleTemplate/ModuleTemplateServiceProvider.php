<?php

declare(strict_types=1);

namespace Yeod\Modules\ModuleTemplate;

use Yeod\Shared\Infrastructure\Module\ModuleServiceProvider;

/**
 * Template module wiring.
 *
 * Copy the whole `ModuleTemplate` directory, rename it and rename this class. The
 * five declarative maps below are the only thing a new module has to fill in.
 *
 * @see \Yeod\Modules\Catalog\CatalogServiceProvider For a fully implemented example.
 */
final class ModuleTemplateServiceProvider extends ModuleServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'module_template';
    }

    /**
     * {@inheritDoc}
     */
    protected function bindings(): array
    {
        return [
            // Domain\Repository\ExampleRepository::class => Infrastructure\Persistence\Eloquent\EloquentExampleRepository::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function commandHandlers(): array
    {
        return [
            // Application\Command\DoSomething\DoSomethingCommand::class => Application\Command\DoSomething\DoSomethingHandler::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function queryHandlers(): array
    {
        return [
            // Application\Query\GetSomething\GetSomethingQuery::class => Application\Query\GetSomething\GetSomethingHandler::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function filamentResources(): array
    {
        return [
            // Presentation\Filament\Resources\ExampleResource::class,
        ];
    }
}
