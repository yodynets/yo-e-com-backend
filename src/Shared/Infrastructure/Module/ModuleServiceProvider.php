<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Module;

use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Yeod\Shared\Infrastructure\Bus\HandlerRegistry;

/**
 * Base service provider every module extends.
 *
 * It is the single wiring point of a module: bindings, command and query handler
 * maps, migrations, routes, translations, views, console commands and Filament
 * contributions. Moving a module to another project means copying its folder and
 * adding one line to `bootstrap/providers.php`.
 *
 * A concrete provider normally only overrides the small declarative methods:
 *
 * ```php
 * final class CatalogServiceProvider extends ModuleServiceProvider
 * {
 *     public function name(): string
 *     {
 *         return 'catalog';
 *     }
 *
 *     protected function bindings(): array
 *     {
 *         return [ProductRepository::class => EloquentProductRepository::class];
 *     }
 * }
 * ```
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Machine readable module name used for config, translation and view namespaces.
     *
     * Example: `catalog`.
     */
    abstract public function name(): string;

    /**
     * Register the module's container bindings and handler maps.
     */
    final public function register(): void
    {
        foreach ($this->bindings() as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }

        foreach ($this->singletons() as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
        }

        $this->app->afterResolving(
            HandlerRegistry::class,
            function (HandlerRegistry $registry): void {
                $registry->registerMany($this->commandHandlers());
                $registry->registerMany($this->queryHandlers());
            },
        );

        if (is_file($config = $this->modulePath('Infrastructure/Config/config.php'))) {
            $this->mergeConfigFrom($config, $this->name());
        }

        $this->app->resolving(
            ModuleRegistry::class,
            function (ModuleRegistry $registry): void {
                $registry->add(
                    $this->name(),
                    $this->filamentResources(),
                    $this->filamentPages(),
                    $this->filamentWidgets(),
                );
            },
        );

        $this->registerModule();
    }

    /**
     * Boot the module: migrations, routes, translations, views, console, Filament.
     */
    final public function boot(): void
    {
        if (is_dir($migrations = $this->modulePath('Infrastructure/Database/Migrations'))) {
            $this->loadMigrationsFrom($migrations);
        }

        if (is_dir($translations = $this->modulePath('Presentation/Lang'))) {
            $this->loadTranslationsFrom($translations, $this->name());
        }

        if (is_dir($views = $this->modulePath('Presentation/Views'))) {
            $this->loadViewsFrom($views, $this->name());
        }

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands($this->consoleCommands());
        }

        foreach ($this->listeners() as $event => $eventListeners) {
            foreach ($eventListeners as $listener) {
                $this->app->make('events')->listen($event, $listener);
            }
        }

        $this->bootModule();
    }

    /**
     * Interface to implementation bindings resolved on every request.
     *
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [];
    }

    /**
     * Interface to implementation bindings resolved once per container.
     *
     * @return array<class-string, class-string>
     */
    protected function singletons(): array
    {
        return [];
    }

    /**
     * Command class to command handler class map.
     *
     * @return array<class-string, class-string>
     */
    protected function commandHandlers(): array
    {
        return [];
    }

    /**
     * Query class to query handler class map.
     *
     * @return array<class-string, class-string>
     */
    protected function queryHandlers(): array
    {
        return [];
    }

    /**
     * Artisan commands exposed by the module.
     *
     * @return list<class-string>
     */
    protected function consoleCommands(): array
    {
        return [];
    }

    /**
     * Domain event name (or class) to listener class map.
     *
     * @return array<class-string|string, list<class-string>>
     */
    protected function listeners(): array
    {
        return [];
    }

    /**
     * Filament resource classes contributed to the admin panel.
     *
     * @return list<class-string>
     */
    protected function filamentResources(): array
    {
        return [];
    }

    /**
     * Filament page classes contributed to the admin panel.
     *
     * @return list<class-string>
     */
    protected function filamentPages(): array
    {
        return [];
    }

    /**
     * Filament widget classes contributed to the admin panel.
     *
     * @return list<class-string>
     */
    protected function filamentWidgets(): array
    {
        return [];
    }

    /**
     * Route middleware applied to `Presentation/Routes/web.php`.
     *
     * @return list<string>
     */
    protected function webMiddleware(): array
    {
        return ['web'];
    }

    /**
     * Route middleware and prefix applied to `Presentation/Routes/api.php`.
     *
     * @return list<string>
     */
    protected function apiMiddleware(): array
    {
        return ['api'];
    }

    /**
     * Extra registration hook for cases the declarative maps cannot express.
     */
    protected function registerModule(): void
    {
        //
    }

    /**
     * Extra boot hook for cases the declarative maps cannot express.
     */
    protected function bootModule(): void
    {
        //
    }

    /**
     * Absolute path inside the module directory.
     *
     * The path is derived by reflection, so a module keeps working after being
     * moved or renamed.
     *
     * @param  string  $relative  Path relative to the module root, for example `Presentation/Routes`.
     */
    protected function modulePath(string $relative = ''): string
    {
        $root = dirname((string) (new ReflectionClass(static::class))->getFileName());

        return $relative === '' ? $root : $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    /**
     * Load the module's route files when they exist.
     */
    private function registerRoutes(): void
    {
        if (is_file($web = $this->modulePath('Presentation/Routes/web.php'))) {
            $this->app->make('router')->middleware($this->webMiddleware())->group($web);
        }

        if (is_file($api = $this->modulePath('Presentation/Routes/api.php'))) {
            $this->app->make('router')
                ->middleware($this->apiMiddleware())
                ->prefix('api')
                ->group($api);
        }
    }
}
