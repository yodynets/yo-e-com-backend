<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Infrastructure\Laravel;

use Illuminate\Support\ServiceProvider;
use Yeod\CommerceLifecycle\Application\AllowAllAuthorizer;
use Yeod\CommerceLifecycle\Application\Archive\ArchiveService;
use Yeod\CommerceLifecycle\Application\Authorizer;
use Yeod\CommerceLifecycle\Contracts\DomainEventDispatcher;
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Infrastructure\Events\LaravelDomainEventDispatcher;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\EloquentArchiveRepository;
use Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent\EloquentFulfillmentRepository;

/**
 * Laravel integration boundary for the commerce lifecycle package.
 */
final class CommerceLifecycleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/commerce-lifecycle.php', 'commerce-lifecycle');

        $this->app->bind(FulfillmentRepository::class, EloquentFulfillmentRepository::class);
        $this->app->bind(ArchiveRepository::class, EloquentArchiveRepository::class);
        $this->app->bind(DomainEventDispatcher::class, LaravelDomainEventDispatcher::class);

        $this->app->singleton(Authorizer::class, static function (): Authorizer {
            $concrete = config('commerce-lifecycle.authorizer', AllowAllAuthorizer::class);

            return app($concrete);
        });

        $this->app->bind(ArchiveService::class, static function (): ArchiveService {
            return new ArchiveService(
                app(ArchiveRepository::class),
                (int) config('commerce-lifecycle.max_snapshot_size', 512),
                (int) config('commerce-lifecycle.max_reason_length', 1000),
                app(Authorizer::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config/commerce-lifecycle.php' => config_path('commerce-lifecycle.php'),
        ], 'commerce-lifecycle-config');

        $this->publishes([
            __DIR__ . '/../../../database/migrations' => database_path('migrations'),
        ], 'commerce-lifecycle-migrations');
    }
}